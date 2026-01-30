@props(['actions' => null])

<x-mary-form {{ $attributes }}>
    {{ $slot }}

    @if($actions)
        <x-slot name="actions">
            {{ $actions }}
        </x-slot>
    @endif
</x-mary-form>