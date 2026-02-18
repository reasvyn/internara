@props(['title' => null])

<x-ui::layouts.base :$title>
    <x-ui::nav {{ $attributes->merge(['class' => 'bg-base-100/80 backdrop-blur-md border-b border-base-200']) }}>
        <x-slot:brand>
            @isset($hamburger)
                {{ $hamburger }}
            @endisset

            {{-- Brand --}}
            @if(slotExists('navbar.brand'))
                @slotRender('navbar.brand')
            @else
                <x-ui::brand />
            @endif

            <x-ui::badge 
                :value="\Illuminate\Support\Str::start(setting('app_version', '0.1.0'), 'v')" 
                variant="metadata" 
                class="ml-2" 
            />
        </x-slot>

        {{-- Right side actions --}}
        <x-slot:actions class="flex items-center space-x-2">
            <livewire:ui::language-switcher />
            <x-ui::theme-toggle />
        </x-slot>
    </x-ui::nav>

    <main id="main-content" class="flex flex-1 flex-col items-center justify-center p-4">
        <x-honeypot />
        {{ $slot }}
    </main>

    <x-ui::footer class="border-t border-base-200" />
</x-ui::layouts.base>
