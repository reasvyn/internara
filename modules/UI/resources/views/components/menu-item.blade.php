@props(['title' => null])

<x-mary-menu-item {{ $attributes->merge(['aria-label' => __($title)]) }} :title="__($title)">
    {{ $slot }}
</x-mary-menu-item>
