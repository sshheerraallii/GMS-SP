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

        // V4-P2: month filter. First visit (no `month` key) defaults to the
        // current month; the form always sends the key, '' meaning all months.
        $month = $request->has('month')
            ? trim((string) $request->get('month'))
            : now('Europe/London')->format('Y-m');
        if ($month !== '' && !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $month = '';
        }

        // Month options: every month that has an invoice, plus the current one.
        $dateExpr = 'COALESCE(issued_at, created_at)';
        $monthOptions = Invoice::query()
            ->selectRaw("DATE_FORMAT({$dateExpr}, '%Y-%m') as ym")
            ->groupBy('ym')->pluck('ym')
            ->push(now('Europe/London')->format('Y-m'))
            ->push($month)
            ->filter()->unique()->sortDesc()->values();

        $tempGuardEventIds = \App\Models\Event::idsWithTempGuards()->flip();

        $invoices = Invoice::query()
            ->with(['event', 'event.client'])
            ->when($month !== '', function ($query) use ($month, $dateExpr) {
                $query->whereRaw("DATE_FORMAT({$dateExpr}, '%Y-%m') = ?", [$month]);
            })
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

        return view('invoices.index', compact('invoices', 'q', 'perPage', 'month', 'monthOptions', 'tempGuardEventIds'));
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
        if ($message = optional($invoice->event)->tempGuardBlockMessage()) {
            return back()->with('error', $message);
        }

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

        $this->notifyInvoice($invoice, 'issued');

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Invoice issued and PDF generated.');
    }

    /** V4-P6: bell notice on invoice status change. Never throws. */
    private function notifyInvoice(Invoice $invoice, string $status): void
    {
        try {
            $label = Invoice::STATUS_LABELS[$status] ?? ucfirst($status);
            \App\Services\NotificationService::notify(
                'invoice',
                'Invoice ' . $invoice->invoice_number . ': ' . $label,
                (optional($invoice->event)->event_name ?: 'Event') . ' — status changed to ' . $label . ' by ' . (auth()->user()->name ?? 'a user') . '.',
                route('invoices.show', $invoice, false),
                ['finance']
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * V4-P2: manual status move — Issued / Paid / Staff payments done, in
     * any order. Deliberate limits:
     *  - a Draft is issued only through issue() (that is what numbers,
     *    locks and stores the PDF), so draft -> paid is refused;
     *  - once issued an invoice can never return to Draft;
     *  - legacy 'void' invoices are not touched.
     * Gate: update-invoice-status (Super Admin + Accountant).
     */
    public function updateStatus(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'status' => 'required|in:issued,paid,staff_paid',
        ]);

        if ($message = optional($invoice->event)->tempGuardBlockMessage()) {
            return back()->with('error', $message);
        }

        if ($invoice->isDraft()) {
            return back()->with('error', 'Issue the draft first — it cannot go straight to ' . Invoice::STATUS_LABELS[$data['status']] . '.');
        }
        if (!$invoice->isLocked()) {
            return back()->with('error', 'This invoice status cannot be changed.');
        }
        if ($invoice->status === $data['status']) {
            return back();
        }

        $new = $data['status'];

        // V4-P7: re-read under a row lock so two simultaneous clicks cannot double-apply / double-notify.
        $changed = DB::transaction(function () use ($invoice, $new) {
            $fresh = Invoice::whereKey($invoice->id)->lockForUpdate()->first();
            if (!$fresh || $fresh->status === $new || !$fresh->isLocked()) {
                return false;
            }
            $fresh->status  = $new;
            $fresh->paid_at = in_array($new, ['paid', 'staff_paid'], true)
                ? ($fresh->paid_at ?? now())
                : null;
            $fresh->save();
            $invoice->setRawAttributes($fresh->getAttributes(), true);
            return true;
        });

        if (!$changed) {
            return back();
        }

        $this->notifyInvoice($invoice, $new);

        return back()->with('success', 'Invoice ' . $invoice->invoice_number . ' set to ' . Invoice::STATUS_LABELS[$new] . '.');
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

        if ($invoice->isLocked()) {
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

        if ($invoice->isLocked()) {
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