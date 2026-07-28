<?php

namespace App\Services\Invoices;

use App\Models\InvoiceSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceNumberGenerator
{
    public function nextForEventName(string $eventName): string
    {
        $prefix = $this->normalizePrefix($eventName);

        return DB::transaction(function () use ($prefix) {
            $seq = InvoiceSequence::query()
                ->where('prefix', $prefix)
                ->lockForUpdate()
                ->first();

            if (!$seq) {
                $seq = InvoiceSequence::create([
                    'prefix' => $prefix,
                    'last_number' => 0,
                ]);
            }

            $seq->last_number = (int)$seq->last_number + 1;
            $seq->save();

            return $prefix . str_pad((string)$seq->last_number, 3, '0', STR_PAD_LEFT);
        });
    }

    private function normalizePrefix(string $eventName): string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $eventName));
        return $clean !== '' ? Str::limit($clean, 20, '') : 'EVENT';
    }
}
