@props([
    'priority' => 'primary', // primary, secondary, tertiary, metadata
    'icon' => null,
    'label' => null,
    'spinner' => true,
    'aos' => null,
])

@php
    $priorityClasses = match ($priority) {
        'primary' => 'btn-accent text-accent-content font-bold',
        'secondary' => 'btn-outline border-base-content/20 hover:bg-base-content/5 text-base-content/80',
        'tertiary' => 'btn-ghost text-base-content/70 hover:text-base-content',
        'metadata' => 'btn-ghost btn-xs text-base-content/50 font-normal hover:bg-transparent lowercase',
        default => 'btn-accent text-accent-content',
    };

    // Enforce minimum touch target (44x44px) for non-metadata buttons
    $targetClasses = $priority !== 'metadata' ? 'min-h-[2.75rem] min-w-[2.75rem]' : '';
@endphp

<x-mary-button
    {{ $attributes->class([$priorityClasses, $targetClasses]) }}
    :icon="$icon"
    :label="$label"
    :spinner="$spinner"
    :data-aos="$aos"
>
    {{ $slot }}
</x-mary-button>