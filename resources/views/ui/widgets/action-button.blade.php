@props([
    'label',
    'icon',
    'link' => '#',
    'color' => 'primary',
])

@php $tsIcon = str_starts_with($icon, 'o-') ? substr($icon, 2) : (str_starts_with($icon, 's-') ? substr($icon, 2) : $icon); @endphp

<x-ts-button
    :text="$label"
    :icon="$tsIcon"
    :href="$link"
    :color="$color"
    class="h-20 w-full rounded-xl font-medium shadow-none"
    wire:navigate
/>
