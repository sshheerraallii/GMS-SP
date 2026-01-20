<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Invoice;
use App\Services\Invoices\GenerateDraftInvoiceForEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'can:manage-invoices']);
    }

    /**
     * List invoices
     */
    public function index()
    {
        $invoices = Invoice::query()
            ->with(['event'])
            ->latest('id')
            ->paginate(20);

        return view('invoices.index', compact('invoices'));
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
            'lines.*.service_date' => ['required', 'date'],
            'lines.*.description'  => ['required', 'string', 'max:255'],

            // UI submits H:i; DB TIME stores H:i:s -> normalize
            'lines.*.shift_start'  => ['required', 'date_format:H:i'],
            'lines.*.shift_end'    => ['required', 'date_format:H:i'],

            'lines.*.quantity'     => ['required', 'integer', 'min:0'],
            'lines.*.rate'         => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($invoice, $data) {

            $vatRate = ($data['vat_mode'] === 'VAT') ? 0.2000 : 0.0000;

            // Replace lines (simple + stable)
            $invoice->lines()->delete();

            $subtotal = 0.0;
            $totalHours = 0.0;

            $lineNo = 1;
            foreach ($data['lines'] as $l) {
                $start = $this->normalizeTime($l['shift_start']); // H:i:s
                $end   = $this->normalizeTime($l['shift_end']);   // H:i:s

                $hours = $this->hoursBetween($start, $end);
                $qty   = (int) $l['quantity'];
                $rate  = (float) $l['rate'];

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
                    'hours'        => round($hours, 2),
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
     * Create draft invoice for an event (idempotent)
     */
    public function generateDraftForEvent(Event $event, GenerateDraftInvoiceForEvent $service)
    {
        $invoice = $service->handle($event);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Draft invoice generated.');
    }

    /**
     * Issue invoice (Mini-Step 4)
     */
   
public function issue(Invoice $invoice)
{
    if (!$invoice->isDraft()) {
        abort(403, 'Only draft invoices can be issued.');
    }

    $invoice->load(['lines', 'event', 'event.client', 'event.supplier']);

    DB::transaction(function () use ($invoice) {

        // 1) Lock invoice first
        $invoice->update([
            'status'    => 'issued',
            'issued_at' => now(),
        ]);

        // 2) Generate + store PDF
        $path = $this->generateAndStorePdf($invoice);

        // 3) Persist path
        $invoice->update([
            'pdf_path' => $path,
        ]);
    });

    return redirect()
        ->route('invoices.show', $invoice)
        ->with('success', 'Invoice issued and PDF generated.');
}


    /**
     * Render PDF in browser (Mini-Step 4)
     */
public function pdf(Request $request, Invoice $invoice)
{
    $invoice->load(['lines', 'event', 'event.client', 'event.supplier']);

    $fresh = $request->boolean('fresh'); // /invoices/{id}/pdf?fresh=1

    // Only Admin + Super Admin can force regenerate
    if ($fresh) {
        $user = $request->user();

        $allowed = $user
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['Super Admin', 'Admin']);

        if (!$allowed) {
            abort(403, 'Not allowed to regenerate PDFs.');
        }
    }

    // If stored PDF exists and not forcing fresh, stream it
    if (!$fresh && $invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path)) {
        $pdf = Storage::disk('local')->get($invoice->pdf_path);

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$invoice->invoice_number.'.pdf"',
        ]);
    }

    // Generate on-the-fly from current invoice DB values
    $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice])
        ->setPaper('a4', 'portrait');

    $output = $pdf->output();

    // If issued, store/overwrite PDF (only happens when fresh=1 OR no stored pdf exists)
    if ($invoice->status === 'issued') {
        $path = $this->storePdfBytes($invoice, $output);
        $invoice->update(['pdf_path' => $path]);
    }

    return response($output, 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'inline; filename="'.$invoice->invoice_number.'.pdf"',
    ]);
}



    /**
     * Download stored PDF (Mini-Step 4)
     */
    public function download(Invoice $invoice)
{
    $invoice->load(['lines', 'event', 'event.client', 'event.supplier']);

    // If stored PDF exists, download it
    if ($invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path)) {
        return Storage::disk('local')->download(
            $invoice->pdf_path,
            $invoice->invoice_number . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    // Otherwise generate and download immediately
    $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice])
        ->setPaper('a4', 'portrait');

    $output = $pdf->output();

    // Store if issued
    if ($invoice->status === 'issued') {
        $path = $this->storePdfBytes($invoice, $output);
        $invoice->update(['pdf_path' => $path]);
    }

    return response($output, 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="'.$invoice->invoice_number.'.pdf"',
    ]);
}

    /**
     * Helpers
     */
    private function normalizeTime(string $t): string
    {
        // H:i:s
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $t)) {
            return $t;
        }

        // H:i -> H:i:s
        if (preg_match('/^\d{2}:\d{2}$/', $t)) {
            return $t . ':00';
        }

        return '00:00:00';
    }

    private function hoursBetween(string $start, string $end): float
    {
        $s = $this->toMinutes($start);
        $e = $this->toMinutes($end);

        if ($e < $s) {
            $e += 1440; // overnight wrap
        }

        return max(0, ($e - $s) / 60);
    }

    private function toMinutes(string $t): int
    {
        // accepts H:i or H:i:s
        $parts = explode(':', $t);
        $h = (int) ($parts[0] ?? 0);
        $m = (int) ($parts[1] ?? 0);

        return ($h * 60) + $m;
    }

    private function generateAndStorePdf(Invoice $invoice): string
{
    $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice])
        ->setPaper('a4', 'portrait');

    $bytes = $pdf->output();

    return $this->storePdfBytes($invoice, $bytes);
}

private function storePdfBytes(Invoice $invoice, string $bytes): string
{
    // Store inside storage/app/invoices/
    $dir = 'invoices';
    $filename = $invoice->invoice_number . '.pdf';
    $path = $dir . '/' . $filename;

    Storage::disk('local')->put($path, $bytes);

    return $path;
}

}
