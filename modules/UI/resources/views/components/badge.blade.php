@props([
    'priority' => 'primary', // primary, secondary, metadata
])

@php
    $priorityClasses = match ($priority) {
        'primary' => 'badge-accent text-white font-semibold',
        'secondary' => 'badge-outline border-base-content/20 text-base-content/70',
        'metadata' => 'badge-ghost badge-sm text-base-content/50 font-normal lowercase',
        default => 'badge-accent',
    };
@endphp

<x-mary-badge {{ $attributes->class([$priorityClasses]) }}>
    {{ $slot }}
</x-mary-badge>
