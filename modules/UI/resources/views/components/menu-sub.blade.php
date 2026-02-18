@props(['title' => null, 'icon' => null])

<x-mary-menu-sub {{ $attributes->merge(['aria-label' => __($title)]) }} :title="__($title)" :icon="$icon">
    {{ $slot }}
</x-mary-menu-sub>
