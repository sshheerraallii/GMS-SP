@props(['label', 'name'])

<div
    class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-emerald-500 transition cursor-pointer"
    ondragover="event.preventDefault()"
    ondrop="handleDrop(event, '{{ $name }}')"
>
    <input
        type="file"
        name="{{ $name }}"
        id="{{ $name }}"
        class="hidden"
        onchange="previewFile(this)"
    >

    <label for="{{ $name }}" class="cursor-pointer">
        <p class="text-sm font-medium text-gray-700">{{ $label }}</p>
        <p class="text-xs text-gray-500">Click or drag & drop</p>
    </label>

    <img class="hidden mt-3 mx-auto h-24 rounded border" />
    <p class="file-name text-xs mt-2 text-gray-600"></p>

    @error($name)
        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
    @enderror
</div>
