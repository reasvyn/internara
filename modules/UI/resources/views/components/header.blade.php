@props(['middle' => null, 'actions' => null])

<x-mary-header {{ $attributes }}>
    @if($middle)
        <x-slot name="middle">
            {{ $middle }}
        </x-slot>
    @endif
    @if($actions)
        <x-slot name="actions">
            {{ $actions }}
        </x-slot>
    @endif
</x-mary-header>