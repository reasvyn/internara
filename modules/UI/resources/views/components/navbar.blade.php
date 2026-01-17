<x-ui::nav {{ $attributes }}>
    <x-slot:brand>
        @isset($hamburger)
            {{ $hamburger }}
        @endisset

        {{-- Brand --}}
        @slotRender('navbar.brand')
    </x-slot>

    {{-- Right side actions --}}
    <x-slot:actions class="space-x-2">
        @slotRender('navbar.actions')
    </x-slot>
</x-ui::nav>
