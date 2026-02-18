@props([
    'sidebar' => null,
    'actions' => null, 
    'footer' => null,
])

<x-mary-main {{ $attributes }}>
    @if($sidebar)
        <x-slot name="sidebar">
            {{ $sidebar }}
        </x-slot>
    @endif

    @if($actions)
        <x-slot name="actions">
            {{ $actions }}
        </x-slot>
    @endif

    @if($footer)
        <x-slot name="footer">
            {{ $footer }}
        </x-slot>
    @endif

    <x-slot name="content">
        {{ $slot }}
    </x-slot>
</x-mary-main>
