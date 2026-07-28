<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Invoice;
use App\Services\Invoices\GenerateDraftInvoiceForEvent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:manage-invoices']);
    }

    /**
     * List invoices (search + paginate)
     */
    public function index(Request $request)
    {
        $perPage = max(1, (int) $request->get('per_page', 20));
        $q = trim((string) $request->get('q', ''));

        $invoices = Invoice::query()
            ->with(['event', 'event.client'])
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('event', function ($eventQ) use ($q) {
                    $eventQ->where('event_name', 'like', "%{$q}%")
                        ->orWhereHas('client', function ($clientQ) use ($q) {
                            $clientQ->where('name', 'like', "%{$q}%");
                        });
                });
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('invoices.index', compact('invoices', 'q', 'perPage'));
    }

    /**
     * View single invoice
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['event', 'lines']);

        return view('invoices.show', compact('invoice'));
    }

    /**
     * Edit draft invoice
     */
    public function edit(Invoice $invoice)
    {
        $invoice->load(['lines', 'event', 'event.client', 'event.supplier']);

        return view('invoices.edit', compact('invoice'));
    }

    /**
     * Update draft invoice (recomputes totals server-side)
     *
     * Locked rule for Phase 1A:
     * - Generated drafts use break-aware net hours from event_shifts
     * - Manual edits use submitted line hours as the final invoice/commercial value
     * - Invoice edits do NOT modify operational truth
     */
    public function update(Request $request, Invoice $invoice)
    {
        if (!$invoice->isDraft()) {
            abort(403, 'Issued invoices are read-only.');
        }

        $data = $request->validate([
            'payment_notes' => ['nullable', 'string', 'max:5000'],
            'vat_mode'      => ['required', Rule::in(['VAT', 'NON_VAT'])],

            'lines' => ['required', 'array', 'min:1'],

            'lines.*.kind'         => ['nullable', Rule::in(['GUARD', 'EXPENSE'])],
            'lines.*.service_date' => ['required', 'date'],
            'lines.*.description'  => ['required', 'string', 'max:255'],

            // Kept for invoice line display/reference
            'lines.*.shift_start'  => ['nullable', 'date_format:H:i'],
            'lines.*.shift_end'    => ['nullable', 'date_format:H:i'],

            'lines.*.quantity'     => ['required', 'integer', 'min:0'],
            'lines.*.hours'        => ['nullable', 'numeric', 'min:0', 'max:24'],
            'lines.*.rate'         => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($invoice, $data) {

            $vatRate = ($data['vat_mode'] === 'VAT') ? 0.2000 : 0.0000;

            $invoice->lines()->delete();

            $subtotal   = 0.0;
            $totalHours = 0.0;
            $lineNo     = 1;

            foreach ($data['lines'] as $l) {
                $kind = strtoupper($l['kind'] ?? 'GUARD');
                $qty  = (int) $l['quantity'];
                $rate = (float) $l['rate'];

                if ($kind === 'EXPENSE') {
                    $start = '00:00:00';
                    $end   = '00:00:00';

                    $amount = round($qty * $rate, 2);
                    $subtotal += $amount;

                    $invoice->lines()->create([
                        'line_no'      => $lineNo++,
                        'service_date' => $l['service_date'],
                        'description'  => $l['description'],
                        'shift_start'  => $start,
                        'shift_end'    => $end,
                        'quantity'     => $qty,
                        'hours'        => 0.00,
                        'rate'         => round($rate, 2),
                        'amount'       => $amount,
                    ]);

                    continue;
                }

                if (empty($l['shift_start']) || empty($l['shift_end'])) {
                    throw ValidationException::withMessages([
                        'lines' => ['Guard lines require shift start and end times.'],
                    ]);
                }

                $start = $this->normalizeTime($l['shift_start']);
                $end   = $this->normalizeTime($l['shift_end']);

                // Locked rule: manual invoice edit uses final submitted hours
                $hours = round(max(0, (float) ($l['hours'] ?? 0)), 2);

                $amount = round($qty * $hours * $rate, 2);

                $subtotal   += $amount;
                $totalHours += ($qty * $hours);

                $invoice->lines()->create([
                    'line_no'      => $lineNo++,
                    'service_date' => $l['service_date'],
                    'description'  => $l['description'],
                    'shift_start'  => $start,
                    'shift_end'    => $end,
                    'quantity'     => $qty,
                    'hours'        => $hours,
                    'rate'         => round($rate, 2),
                    'amount'       => $amount,
                ]);
            }

            $vatAmount = round($subtotal * (float) $vatRate, 2);
            $total     = round($subtotal + $vatAmount, 2);

            $invoice->update([
                'payment_notes' => $data['payment_notes'] ?? null,
                'vat_rate'      => $vatRate,
                'total_hours'   => round($totalHours, 2),
                'subtotal'      => round($subtotal, 2),
                'vat_amount'    => $vatAmount,
                'total'         => $total,
            ]);
        });

        return redirect()
            ->route('invoices.edit', $invoice)
            ->with('success', 'Invoice draft saved.');
    }

    /**
     * Create draft invoice for a single event (idempotent)
     */
    public function generateDraftForEvent(Event $event, GenerateDraftInvoiceForEvent $service)
    {
        try {
            $invoice = $service->handle($event);

            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', 'Draft invoice generated.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    /**
     * Issue invoice (locks + stores PDF)
     */
    public function issue(Invoice $invoice)
    {
        if (!$invoice->isDraft()) {
            abort(403, 'Only draft invoices can be issued.');
        }

        $invoice->load(['lines', 'event', 'event.client', 'event.supplier']);

        DB::transaction(function () use ($invoice) {
            $invoice->update([
                'status'    => 'issued',
                'issued_at' => now(),
            ]);

            $path = $this->generateAndStorePdf($invoice);

            $invoice->update([
                'pdf_path' => $path,
            ]);
        });

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Invoice issued and PDF generated.');
    }

    /**
     * Render PDF inline
     */
    public function pdf(Request $request, Invoice $invoice)
    {
        $invoice->load(['lines', 'event', 'event.client', 'event.supplier']);

        $fresh = $request->boolean('fresh');

        if (!$fresh && $invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path)) {
            $pdf = Storage::disk('local')->get($invoice->pdf_path);

            return response($pdf, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $invoice->invoice_number . '.pdf"',
            ]);
        }

        $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice])->setPaper('a4', 'portrait');
        $output = $pdf->output();

        if ($invoice->status === 'issued') {
            $path = $this->storePdfBytes($invoice, $output);
            $invoice->update(['pdf_path' => $path]);
        }

        return response($output, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $invoice->invoice_number . '.pdf"',
        ]);
    }

    /**
     * Download PDF
     */
    public function download(Invoice $invoice)
    {
        $invoice->load(['lines', 'event', 'event.client', 'event.supplier']);

        if ($invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path)) {
            return Storage::disk('local')->download(
                $invoice->pdf_path,
                $invoice->invoice_number . '.pdf',
                ['Content-Type' => 'application/pdf']
            );
        }

        $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice])->setPaper('a4', 'portrait');
        $output = $pdf->output();

        if ($invoice->status === 'issued') {
            $path = $this->storePdfBytes($invoice, $output);
            $invoice->update(['pdf_path' => $path]);
        }

        return response($output, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $invoice->invoice_number . '.pdf"',
        ]);
    }

    private function normalizeTime(string $t): string
    {
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $t)) {
            return $t;
        }
        if (preg_match('/^\d{2}:\d{2}$/', $t)) {
            return $t . ':00';
        }
        return '00:00:00';
    }

    private function generateAndStorePdf(Invoice $invoice): string
    {
        $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice])->setPaper('a4', 'portrait');
        return $this->storePdfBytes($invoice, $pdf->output());
    }

    private function storePdfBytes(Invoice $invoice, string $bytes): string
    {
        $dir = 'invoices';
        $filename = $invoice->invoice_number . '.pdf';
        $path = $dir . '/' . $filename;

        Storage::disk('local')->put($path, $bytes);

        return $path;
    }
}