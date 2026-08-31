<?php

namespace App\Exports;

use App\Models\Event;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EventStaffSheetExport implements FromCollection, WithHeadings
{
    public function __construct(private int $eventId) {}

    public function headings(): array
    {
        return [
            'Sr',
            'First Names',
            'Last Name',
            'Role',
            'SIA',
            'Expiry',
            'DOB',
            ' Address',
            'NI Number',
            'Sharecode',
            'Email',
            'Contact',
            'Sort Code',
            'Account Number',
            'Beneficiary',
        ];
    }

    public function collection(): Collection
    {
        $guards = DB::table('event_guard')
            ->join('security_guards', 'security_guards.id', '=', 'event_guard.guard_id')
            ->where('event_guard.event_id', $this->eventId)
            ->select([
                'security_guards.fullname',
                'security_guards.dob',
                'security_guards.category',
                'security_guards.license_number',
                'security_guards.license_exp_date',
                'security_guards.adresse',
                'security_guards.ni_number',
                'security_guards.rtw_share_code',
                'security_guards.email_address',
                'security_guards.phone_number',
                'security_guards.sort_code',
                'security_guards.account_number',
                'security_guards.beneficiary_name',
            ])
            ->get();

        // Build rows + sort exactly:
        // 1) SIA first
        // 2) Within each group: alphabetical by Last Name, then First Names
        $rows = $guards->map(function ($g) {
            $full = trim((string) $g->fullname);

            // Best-effort name split: last token = last name
            $parts = preg_split('/\s+/', $full);
            $last = '';
            $first = $full;

            if (is_array($parts) && count($parts) >= 2) {
                $last = array_pop($parts);
                $first = implode(' ', $parts);
            }

            return [
                'first' => $first,
                'last' => $last,
                'role' => (string) $g->category, // SIA / Steward
                'sia' => (string) ($g->license_number ?? ''),
                'expiry' => $g->license_exp_date ? (string) $g->license_exp_date : '',
                'dob' => $g->dob ? (string) $g->dob : '',
                'address' => (string) ($g->adresse ?? ''),
                'ni' => (string) ($g->ni_number ?? ''),
                'sharecode' => (string) ($g->rtw_share_code ?? ''),
                'email' => (string) ($g->email_address ?? ''),
                'contact' => (string) ($g->phone_number ?? ''),
                'sort_code' => (string) ($g->sort_code ?? ''),
                'account_number' => (string) ($g->account_number ?? ''),
                'beneficiary' => (string) ($g->beneficiary_name ?? ''),
            ];
        })->sort(function ($a, $b) {
            $rank = fn($role) => strtolower($role) === 'sia' ? 0 : 1;

            $ra = $rank($a['role']);
            $rb = $rank($b['role']);
            if ($ra !== $rb) return $ra <=> $rb;

            $la = mb_strtolower($a['last']);
            $lb = mb_strtolower($b['last']);
            if ($la !== $lb) return $la <=> $lb;

            return mb_strtolower($a['first']) <=> mb_strtolower($b['first']);
        })->values();

        // Final output with Sr column
        $out = [];
        foreach ($rows as $idx => $r) {
            $out[] = [
                $idx + 1,
                $r['first'],
                $r['last'],
                $r['role'],
                $r['sia'],
                $r['expiry'],
                $r['dob'],
                $r['address'],
                $r['ni'],
                $r['sharecode'],
                $r['email'],
                $r['contact'],
                $r['sort_code'],
                $r['account_number'],
                $r['beneficiary'],
            ];
        }

        return collect($out);
    }
}
