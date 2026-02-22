@props([
    'hamburger' => null,
])

<x-ui::nav 
    {{ $attributes->merge(['class' => 'bg-base-100/80 backdrop-blur-md border-b border-base-200 shadow-sm sticky top-0 z-40']) }}
>
    <x-slot:hamburger>
        {{ $hamburger }}
    </x-slot:hamburger>

    <x-slot:brand>
        @slotRender('navbar.brand')
    </x-slot:brand>

    <div class="flex items-center gap-4">
        @slotRender('navbar.items')
    </div>

    <x-slot:actions>
        @slotRender('navbar.actions', ['filter' => 'livewire:ui::language-switcher'])
        @slotRender('navbar.actions', ['filter' => 'ui::user-menu'])
    </x-slot:actions>
</x-ui::nav>
