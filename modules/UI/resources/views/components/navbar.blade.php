@props(['aos' => 'fade-down'])

<x-ui::nav 
    {{ $attributes->merge(['class' => 'bg-base-100/80 backdrop-blur-md border-b border-base-200 shadow-sm sticky top-0 z-30']) }}
    :data-aos="$aos"
>
    <x-slot:brand>
        @isset($hamburger)
            <div class="flex items-center">
                {{ $hamburger }}
            </div>
        @endisset

        {{-- Brand --}}
        <div class="flex items-center gap-2">
            @slotRender('navbar.brand')
        </div>
    </x-slot>

    <div class="flex-1 flex justify-center gap-4">
        @slotRender('navbar.items')
    </div>

    {{-- Right side actions --}}
    <x-slot:actions class="space-x-2 flex items-center">
        @slotRender('navbar.actions')
    </x-slot:actions>
</x-ui::nav>