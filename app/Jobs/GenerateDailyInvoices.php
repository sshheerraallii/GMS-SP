<?php

namespace App\Jobs;

use App\Models\Event;
use App\Services\Invoices\GenerateDraftInvoiceForEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateDailyInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(GenerateDraftInvoiceForEvent $service): void
    {
        $yesterday = now()->subDay()->toDateString();

        $events = Event::query()
            ->whereDate('end_date', $yesterday)
            ->get();

        foreach ($events as $event) {
            $service->handle($event); // idempotent
        }
    }
}
