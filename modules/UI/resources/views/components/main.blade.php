@props([
    'sidebar' => null,
    'actions' => null, 
    'footer' => null,
    'aos' => null,
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
        <div @if($aos) data-aos="{{ $aos }}" @endif>
            {{ $slot }}
        </div>
    </x-slot>
</x-mary-main>
