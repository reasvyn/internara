@props([
    'label',
    'icon',
    'link' => '#',
    'color' => 'primary',
    'outline' => false,
])

@php
    $tsIcon = str_starts_with($icon, 'o-') ? substr($icon, 2) : (str_starts_with($icon, 's-') ? substr($icon, 2) : $icon);
    // x-ts-button only knows primary, secondary, black and the Tailwind palette
    // names: anything else renders with no colour classes at all, leaving an
    // invisible tile. Fall back to an outlined primary instead.
    $tsColors = ['primary', 'secondary', 'black', 'slate', 'gray', 'zinc', 'neutral', 'stone', 'red',
        'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue',
        'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose'];
    $tsColor = in_array($color, $tsColors, true) ? $color : 'primary';
    $isOutline = $outline || ! in_array($color, $tsColors, true);
@endphp

<x-ts-button
    :text="$label"
    :icon="$tsIcon"
    :href="$link"
    :color="$tsColor"
    :outline="$isOutline"
    class="h-20 w-full rounded-xl font-medium shadow-none"
    wire:navigate
/>
