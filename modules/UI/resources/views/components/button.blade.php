@props([
    'priority' => 'primary', // primary, secondary, tertiary, metadata
    'icon' => null,
    'label' => null,
    'spinner' => true,
    'aos' => null,
])

@php
    $priorityClasses = match ($priority) {
        'primary' => 'btn-primary text-primary-content font-bold',
        'secondary' => 'btn-outline border-base-content/20 hover:bg-base-content/5 text-base-content/80',
        'tertiary' => 'btn-ghost text-base-content/70 hover:text-base-content',
        'metadata' => 'btn-ghost btn-xs text-base-content/50 font-normal hover:bg-transparent lowercase',
        default => 'btn-primary text-primary-content',
    };

    // Enforce minimum touch target (44x44px) for non-metadata buttons
    $targetClasses = $priority !== 'metadata' ? 'min-h-[2.75rem] min-w-[2.75rem]' : '';
    
    // Accessibility: Use label as aria-label if it's an icon-only button
    $ariaLabel = $attributes->get('aria-label') ?? $label;
@endphp

<x-mary-button
    {{ $attributes->merge(['aria-label' => $ariaLabel])->class([$priorityClasses, $targetClasses]) }}
    :icon="$icon"
    :label="$label"
    :spinner="$spinner"
    :data-aos="$aos"
>
    {{ $slot }}
</x-mary-button>