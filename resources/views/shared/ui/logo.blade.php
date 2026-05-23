@props([
    'size' => '8',
    'invert' => false,
])

@php
    $sizeClass = str_starts_with((string) $size, 'size-') ? $size : 'size-'.$size;
    $invertClass = $invert ? 'brightness-0 invert' : '';
@endphp

<img
    src="{{ brand('logo') }}"
    alt="{{ brand('name') }}"
    {{ $attributes->merge(['class' => "object-contain {$sizeClass} {$invertClass}"]) }}
/>
