<?php

namespace App\Services\GuardInvoices;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\GuardInvoice;
use Illuminate\Support\Facades\DB;

class GenerateDraftGuardInvoicesForEvent
{
    public function __construct(
        protected GenerateDraftGuardInvoiceForEventGuard $single
    ) {}

    public function handle(Event $event): array
    {
        return DB::transaction(function () use ($event) {

            $guardIds = EventShift::query()
                ->where('event_id', $event->id)
                ->whereNull('cancelled_at')
                ->whereNotNull('guard_id')
                ->distinct()
                ->pluck('guard_id')
                ->values()
                ->all();

            $created = 0;
            $rebuilt = 0;
            $skipped = 0;

            foreach ($guardIds as $guardId) {
                $existing = GuardInvoice::query()
                    ->where('event_id', $event->id)
                    ->where('guard_id', $guardId)
                    ->lockForUpdate()
                    ->first();

                if ($existing && $existing->status !== 'draft') {
                    $skipped++;
                    continue;
                }

                $wasNew = !$existing;

                $this->single->handle($event, (int) $guardId);

                $wasNew ? $created++ : $rebuilt++;
            }

            return [
                'total_guards' => count($guardIds),
                'created' => $created,
                'rebuilt' => $rebuilt,
                'skipped' => $skipped,
                'guard_ids' => $guardIds,
            ];
        });
    }
}
