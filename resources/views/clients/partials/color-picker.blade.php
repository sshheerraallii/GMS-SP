{{--
    Client colour picker (v2 — self-contained).
    Renders 50 curated swatches as radio inputs plus a "no colour" option.
    All styling is embedded below so the picker works regardless of the
    state of the compiled Tailwind bundle. Field name/value contract is
    unchanged (name="color", hex value) — no controller changes needed.
    Usage:
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

<style>
    .gms-swatches{display:flex;flex-wrap:wrap;gap:8px;margin-top:4px}
    .gms-swatches label{position:relative;cursor:pointer;line-height:0}
    .gms-swatches input{position:absolute;opacity:0;width:1px;height:1px;pointer-events:none}
    .gms-swatches .sw{
        display:inline-block;width:28px;height:28px;border-radius:9999px;
        border:1px solid rgba(0,0,0,.15);box-sizing:border-box;
        transition:transform .1s ease,box-shadow .1s ease;
    }
    .gms-swatches label:hover .sw{transform:scale(1.12)}
    .gms-swatches input:checked + .sw{
        box-shadow:0 0 0 2px #fff,0 0 0 4px #141d2e;
        transform:scale(1.08);
    }
    .gms-swatches input:focus-visible + .sw{box-shadow:0 0 0 2px #fff,0 0 0 4px #3e69ab}
    .gms-swatches .sw-none{
        display:inline-flex;align-items:center;justify-content:center;
        width:28px;height:28px;border-radius:9999px;box-sizing:border-box;
        border:1px dashed #94a3b8;background:#fff;color:#94a3b8;
        font:600 12px/1 sans-serif;
    }
    .gms-swatches input:checked + .sw-none{box-shadow:0 0 0 2px #fff,0 0 0 4px #141d2e}
    .gms-color-hint{margin-top:6px;font-size:12px;color:#64748b}
</style>

<div>
    <label class="block text-gray-700 font-medium mb-1">
        Colour
        <span class="text-xs font-normal text-gray-400">(optional &mdash; tints this client's rows on chaseup, events &amp; reports)</span>
    </label>

    <div class="gms-swatches">
        <label title="No colour">
            <input type="radio" name="color" value="" {{ empty($current) ? 'checked' : '' }}>
            <span class="sw-none">&#10005;</span>
        </label>

        @foreach($palette as $hex)
            <label title="{{ $hex }}">
                <input type="radio" name="color" value="{{ $hex }}"
                       {{ strtolower((string) $current) === strtolower($hex) ? 'checked' : '' }}>
                <span class="sw" style="background: {{ $hex }};"></span>
            </label>
        @endforeach
    </div>

    <p class="gms-color-hint">Click a circle to select. The dark ring shows the current choice.</p>

    @error('color')
        <p class="text-red-600 mt-1 text-sm">{{ $message }}</p>
    @enderror
</div>
