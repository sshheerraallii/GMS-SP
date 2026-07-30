{{--
    Client colour picker. Renders 50 curated swatches as radio inputs
    (no JS; uses peer-checked for the selected ring) plus a "no colour"
    option. Pass the current value:
        @include('clients.partials.color-picker', ['current' => old('color', $client->color ?? null)])
--}}
@php
    $current = $current ?? old('color');
    $palette = [
        '#ef4444', '#f87171', '#dc2626', '#f97316', '#fb923c',
        '#ea580c', '#f59e0b', '#fbbf24', '#d97706', '#eab308',
        '#facc15', '#ca8a04', '#84cc16', '#a3e635', '#65a30d',
        '#22c55e', '#4ade80', '#16a34a', '#10b981', '#34d399',
        '#059669', '#14b8a6', '#2dd4bf', '#0d9488', '#06b6d4',
        '#22d3ee', '#0891b2', '#0ea5e9', '#38bdf8', '#0284c7',
        '#3b82f6', '#60a5fa', '#2563eb', '#6366f1', '#818cf8',
        '#4f46e5', '#8b5cf6', '#a78bfa', '#7c3aed', '#a855f7',
        '#c084fc', '#9333ea', '#d946ef', '#e879f9', '#c026d3',
        '#ec4899', '#f472b6', '#db2777', '#f43f5e', '#fb7185',
    ];
@endphp

<div>
    <label class="block text-gray-700 font-medium mb-1">
        Colour
        <span class="text-xs font-normal text-gray-400">(optional — tints this client's rows on chaseup, events &amp; reports)</span>
    </label>

    <div class="flex flex-wrap gap-2">
        <label class="cursor-pointer" title="No colour">
            <input type="radio" name="color" value="" class="peer sr-only" {{ empty($current) ? 'checked' : '' }}>
            <span class="flex h-7 w-7 items-center justify-center rounded-full border border-gray-300 text-[11px] text-gray-400 peer-checked:ring-2 peer-checked:ring-offset-1 peer-checked:ring-gray-800">✕</span>
        </label>

        @foreach($palette as $hex)
            <label class="cursor-pointer" title="{{ $hex }}">
                <input type="radio" name="color" value="{{ $hex }}" class="peer sr-only"
                       {{ strtolower((string) $current) === strtolower($hex) ? 'checked' : '' }}>
                <span class="block h-7 w-7 rounded-full border border-black/10 peer-checked:ring-2 peer-checked:ring-offset-1 peer-checked:ring-gray-800"
                      style="background: {{ $hex }};"></span>
            </label>
        @endforeach
    </div>

    @error('color')
        <p class="text-red-600 mt-1 text-sm">{{ $message }}</p>
    @enderror
</div>
