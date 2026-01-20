@props(['label', 'name'])

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">
        {{ $label }}
    </label>

    <select
        name="{{ $name }}"
        class="
            w-full
            rounded-lg
            border
            border-gray-300
            bg-white
            px-3 py-2
            text-gray-900
            focus:outline-none
            focus:ring-2
            focus:ring-emerald-500
            focus:border-emerald-500
        "
    >
        {{ $slot }}
    </select>

    @error($name)
        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
    @enderror
</div>
