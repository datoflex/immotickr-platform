@props(['unit', 'width' => 'w-full'])

<div class="relative mt-1 {{ $width }}">
    <x-text-input
        type="number"
        {{ $attributes->merge(['class' => 'block w-full pe-9 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none']) }}
    />
    <span class="pointer-events-none absolute inset-y-0 end-0 flex items-center pe-3 text-sm text-gray-500" aria-hidden="true">{{ $unit }}</span>
</div>
