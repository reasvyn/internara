@props([
    'align' => 'center',
    'gap' => '1.5',
])

@php
    $alignmentClass = match ($align) {
        'start' => 'justify-start',
        'end' => 'justify-end',
        default => 'justify-center',
    };
@endphp

<div {{ $attributes->class(["flex items-center {$alignmentClass} gap-1.5 py-1"]) }}>{{ $slot }}</div>
