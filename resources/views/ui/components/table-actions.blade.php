@props([
    'align' => 'end',
    'gap' => '1.5',
])

@php
    $alignmentClass = match ($align) {
        'start' => 'justify-start',
        'center' => 'justify-center',
        default => 'justify-end',
    };
@endphp

<div {{ $attributes->class(["flex items-center {$alignmentClass} gap-1.5 py-1"]) }}>{{ $slot }}</div>
