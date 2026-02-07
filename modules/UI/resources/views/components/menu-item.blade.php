@props(['title' => null])

<x-mary-menu-item {{ $attributes->merge(['aria-label' => $title]) }} :title="$title">
    {{ $slot }}
</x-mary-menu-item>
