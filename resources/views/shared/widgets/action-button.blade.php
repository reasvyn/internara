@props([
    'label',
    'icon',
    'link' => '#',
    'color' => 'btn-primary',
])

<x-mary-button
    :label="$label"
    :icon="$icon"
    :link="$link"
    :class="$color . ' h-20 rounded-xl font-medium shadow-none w-full'"
    wire:navigate
/>
