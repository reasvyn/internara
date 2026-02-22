@props([
    'title' => null,
    'icon' => null,
    'spinner' => null,
])

@php
    $resolvedSpinner = $spinner ?? $attributes->get('wire:click');
@endphp

<x-mary-menu-item 
    {{ $attributes->merge(['aria-label' => __($title)]) }} 
    :title="__($title)" 
    :icon="$icon"
    :spinner="$resolvedSpinner"
>
    {{ $slot }}
</x-mary-menu-item>
