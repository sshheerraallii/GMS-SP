<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\GuardInvoice;
use App\Services\GuardInvoices\GenerateDraftGuardInvoicesForEvent;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class GuardInvoiceController extends Controller
{
   public function index(Request $request)
{
    $this->authorize('manage-invoices');

    $perPage = max(1, min((int) $request->get('per_page', 20), 100));
    $qText = trim((string) $request->get('q', ''));

    // Determine real column names safely (no guessing)
    $eventNameCol = null;
    if (Schema::hasColumn('events', 'event_name')) {
        $eventNameCol = 'event_name';
    } elseif (Schema::hasColumn('events', 'name')) {
        $eventNameCol = 'name';
    }

    $guardNameCol = null;
    if (Schema::hasColumn('security_guards', 'fullname')) {
        $guardNameCol = 'fullname';
    } elseif (Schema::hasColumn('security_guards', 'full_name')) {
        $guardNameCol = 'full_name';
    } elseif (Schema::hasColumn('security_guards', 'name')) {
        $guardNameCol = 'name';
    }

    $q = GuardInvoice::query()
        ->with(['event', 'securityGuard'])
        ->latest('id');

    // Existing exact filters (safe)
    if ($request->filled('event_id')) {
        $q->where('event_id', $request->integer('event_id'));
    }

    if ($request->filled('guard_id')) {
        $q->where('guard_id', $request->integer('guard_id'));
    }

    if ($request->filled('status')) {
        $q->where('status', (string) $request->get('status'));
    }

    if ($request->filled('invoice_number')) {
        $q->where('invoice_number', 'like', '%' . (string) $request->get('invoice_number') . '%');
    }

    // DB-level search (only against columns that exist)
    if ($qText !== '') {
        $q->where(function ($outer) use ($qText, $eventNameCol, $guardNameCol) {

            // Always safe: invoice_number
            $outer->where('invoice_number', 'like', "%{$qText}%");

            // Event name search (only if we found a valid column)
            if ($eventNameCol) {
                $outer->orWhereHas('event', function ($eventQ) use ($qText, $eventNameCol) {
                    $eventQ->where($eventNameCol, 'like', "%{$qText}%");
                });
            }

            // Guard name search (only if we found a valid column)
            if ($guardNameCol) {
                $outer->orWhereHas('securityGuard', function ($guardQ) use ($qText, $guardNameCol) {
                    $guardQ->where($guardNameCol, 'like', "%{$qText}%");
                });
            }
        });
    }

    $guardInvoices = $q->paginate($perPage)->withQueryString();

    return view('guard-invoices.index', compact('guardInvoices'));
}


    public function show(GuardInvoice $guardInvoice)
    {
        $this->authorize('manage-invoices');

        $guardInvoice->load(['event', 'securityGuard', 'lines']);

        return view('guard-invoices.show', compact('guardInvoice'));
    }

    public function generateForEvent(Event $event, GenerateDraftGuardInvoicesForEvent $bulk)
    {
        $this->authorize('manage-invoices');

        $result = $bulk->handle($event);

        return redirect()
            ->back()
            ->with('success', "Guard invoices generated for {$result['total_guards']} guard(s). Created: {$result['created']}, Rebuilt: {$result['rebuilt']}, Skipped: {$result['skipped']}.");
    }

    public function edit(GuardInvoice $guardInvoice)
    {
        $this->authorize('manage-invoices');

        if ($guardInvoice->status !== 'draft') {
            return redirect()
                ->route('guard-invoices.show', $guardInvoice)
                ->with('success', 'Only draft guard invoices can be edited.');
        }

        $guardInvoice->load(['event', 'securityGuard', 'lines']);

        return view('guard-invoices.edit', compact('guardInvoice'));
    }

    public function update(Request $request, GuardInvoice $guardInvoice)
    {
        $this->authorize('manage-invoices');

        if ($guardInvoice->status !== 'draft') {
            return redirect()
                ->route('guard-invoices.show', $guardInvoice)
                ->with('success', 'Only draft guard invoices can be edited.');
        }

        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.service_date' => ['required', 'date'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.shift_start' => ['required', 'date_format:H:i'],
            'lines.*.shift_end' => ['required', 'date_format:H:i'],
            'lines.*.hours' => ['required', 'numeric', 'min:0', 'max:24'],
            'lines.*.rate' => ['required', 'numeric', 'min:0'],
        ]);

        $guardInvoice->load('lines');

        $totalHours = 0.0;
        $subtotal = 0.0;

        foreach ($guardInvoice->lines as $line) {
            if (!isset($data['lines'][$line->id])) {
                continue;
            }

            $row = $data['lines'][$line->id];

            $hours = $this->fmt2((float) $row['hours']);
            $rate  = $this->fmt2((float) $row['rate']);
            $amount = $this->fmt2($hours * $rate);

            $line->update([
                'service_date' => $row['service_date'],
                'description'  => ($row['description'] ?? '') !== '' ? $row['description'] : 'Guard Services',
                'shift_start'  => $row['shift_start'] . ':00',
                'shift_end'    => $row['shift_end'] . ':00',
                'hours'        => $hours,
                'rate'         => $rate,
                'amount'       => $amount,
            ]);

            $totalHours += $hours;
            $subtotal += $amount;
        }

        $guardInvoice->update([
            'total_hours' => $this->fmt2($totalHours),
            'subtotal'    => $this->fmt2($subtotal),
            'total'       => $this->fmt2($subtotal),
        ]);

        return redirect()
            ->route('guard-invoices.show', $guardInvoice)
            ->with('success', 'Guard invoice updated.');
    }

    public function issue(GuardInvoice $guardInvoice)
    {
        $this->authorize('manage-invoices');

        if ($guardInvoice->status !== 'draft') {
            return redirect()
                ->route('guard-invoices.show', $guardInvoice)
                ->with('success', 'Only draft guard invoices can be issued.');
        }

        $guardInvoice->load(['event', 'securityGuard', 'lines']);

        $pdf = Pdf::loadView('guard-invoices.pdf', [
            'guardInvoice' => $guardInvoice,
        ]);

        $filename = $guardInvoice->invoice_number . '.pdf';
        $path = 'guard-invoices/' . $filename;

        Storage::disk('local')->put($path, $pdf->output());

        $guardInvoice->update([
            'status'    => 'issued',
            'issued_at' => now(),
            'pdf_path'  => $path,
        ]);

        return redirect()
            ->route('guard-invoices.show', $guardInvoice)
            ->with('success', 'Guard invoice issued and PDF stored.');
    }

    public function pdf(GuardInvoice $guardInvoice)
    {
        $this->authorize('manage-invoices');

        $guardInvoice->load(['event', 'securityGuard', 'lines']);

        if ($guardInvoice->pdf_path && Storage::disk('local')->exists($guardInvoice->pdf_path)) {
            return response()->file(storage_path('app/' . $guardInvoice->pdf_path));
        }

        $pdf = Pdf::loadView('guard-invoices.pdf', [
            'guardInvoice' => $guardInvoice,
        ]);

        return $pdf->stream($guardInvoice->invoice_number . '.pdf');
    }

    public function download(GuardInvoice $guardInvoice)
    {
        $this->authorize('manage-invoices');

        $guardInvoice->load(['event', 'securityGuard', 'lines']);

        if ($guardInvoice->pdf_path && Storage::disk('local')->exists($guardInvoice->pdf_path)) {
            return Storage::disk('local')->download(
                $guardInvoice->pdf_path,
                $guardInvoice->invoice_number . '.pdf'
            );
        }

        $pdf = Pdf::loadView('guard-invoices.pdf', [
            'guardInvoice' => $guardInvoice,
        ]);

        return $pdf->download($guardInvoice->invoice_number . '.pdf');
    }

    public function markPaid(GuardInvoice $guardInvoice)
    {
        $this->authorize('manage-invoices');

        if ($guardInvoice->status !== 'issued') {
            return redirect()
                ->route('guard-invoices.show', $guardInvoice)
                ->with('success', 'Only issued guard invoices can be marked as paid.');
        }

        $guardInvoice->update([
            'status'  => 'paid',
            'paid_at' => now(),
        ]);

        return redirect()
            ->route('guard-invoices.show', $guardInvoice)
            ->with('success', 'Guard invoice marked as paid.');
    }

    private function fmt2(float $v): float
    {
        return (float) number_format($v, 2, '.', '');
    }
}
