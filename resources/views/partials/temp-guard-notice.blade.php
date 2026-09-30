{{-- V4-P5: shown wherever an event still has a temporary guard. Expects $event. --}}
@php $tempSlots = $event->tempGuardSlots()->orderBy('date')->orderBy('slot_no')->get(); @endphp
@if($tempSlots->isNotEmpty())
    <div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
        <div class="font-semibold">
            Temporary guard on this event &mdash; invoices and guard payments are blocked until replaced.
        </div>
        <ul class="mt-1 list-disc pl-5 text-xs">
            @foreach($tempSlots as $ts)
                <li>{{ optional($ts->date)->format('d M Y') }} &middot; slot {{ $ts->slot_no }} &middot; {{ $ts->temp_phone }}</li>
            @endforeach
        </ul>
    </div>
@endif
