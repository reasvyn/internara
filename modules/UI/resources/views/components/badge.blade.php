@props([
    'priority' => 'primary', // primary, secondary, metadata
    'aos' => null,
])

@php
    $priorityClasses = match ($priority) {
        'primary' => 'badge-accent text-accent-content font-semibold',
        'secondary' => 'badge-outline border-base-content/20 text-base-content/70',
        'metadata' => 'badge-ghost badge-sm text-base-content/50 font-normal lowercase',
        default => 'badge-accent text-accent-content',
    };
php@endphp

<x-mary-badge 
    {{ $attributes->class([$priorityClasses]) }}
    :data-aos="$aos"
>
    {{ $slot }}
</x-mary-badge>
