<x-mary-nav {{ $attributes }}>
    @isset($actions)
        <x-slot:actions>
            {{ $actions }}
        </x-slot:actions>
    @endisset
</x-mary-nav>
