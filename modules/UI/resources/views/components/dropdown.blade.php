<x-mary-dropdown {{ $attributes }}>
    @isset($trigger)
        <x-slot:trigger>
            {{ $trigger }}
        </x-slot:trigger>
    @endisset
    {{ $slot }}
</x-mary-dropdown>
