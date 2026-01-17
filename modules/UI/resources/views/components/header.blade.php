<x-mary-header {{ $attributes }}>
    @isset($middle)
        <x-slot:middle>
            {{ $middle }}
        </x-slot:middle>
    @endisset
    @isset($actions)
        <x-slot:actions>
            {{ $actions }}
        </x-slot:actions>
    @endisset
</x-mary-header>
