<x-ui::nav {{ $attributes }}>
    <x-slot:brand>
        @isset($hamburger)
            {{ $hamburger }}
        @endisset

        {{-- Brand --}}
        @slotRender('navbar.brand')
    </x-slot>

    <div class="flex-1 flex justify-center gap-4">
        @slotRender('navbar.items')
    </div>

    {{-- Right side actions --}}
    <x-slot:actions class="space-x-2">
        @slotRender('navbar.actions')
    </x-slot>
</x-ui::nav>
