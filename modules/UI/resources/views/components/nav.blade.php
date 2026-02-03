<x-mary-nav {{ $attributes }}>
    @isset($brand)
        <x-slot:brand>
            {{ $brand }}
        </x-slot:brand>
    @endisset

    @isset($actions)
        <x-slot:actions>
            {{ $actions }}
        </x-slot:actions>
    @endisset
</x-mary-nav>
