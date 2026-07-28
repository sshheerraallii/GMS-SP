@props([
    'label',
    'name',
    'type' => 'text',
    'value' => null,
])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">
        {{ $label }}
    </label>

    <input
        id="{{ $name }}"
        type="{{ $type }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        {{ $attributes->merge([
            'class' => '
                w-full
                rounded-lg
                border
                border-gray-300
                bg-white
                px-3 py-2
                text-gray-900
                placeholder-gray-400
                focus:outline-none
                focus:ring-2
                focus:ring-emerald-500
                focus:border-emerald-500
            '
        ]) }}
    >

    @error($name)
        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
    @enderror
</div>
