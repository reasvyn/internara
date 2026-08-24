@props([
    'title' => null,
    'header' => null,
    'footer' => null,
    'context' => null,
])

<x-core::layouts.base :$title>
    <x-ts-layout>
        <x-slot:menu>
            <x-core::layouts.sidebar />
        </x-slot:menu>

        <x-slot:header>
            <x-core::layouts.header :$header />
        </x-slot:header>

        <div class="flex flex-1 flex-col">
            <div class="container mx-auto flex max-w-7xl flex-1 flex-col px-4 py-5 md:px-6 lg:px-8">
                @if ($context)
                    <nav aria-label="Breadcrumb" class="text-base-content/60 mb-5 flex items-center gap-2 text-xs">
                        <a wire:navigate href="{{ route('dashboard') }}" class="hover:text-primary transition-colors">
                            {{ brand('name') }}
                        </a>
                        <span class="text-base-content/60">/</span>
                        <span class="text-primary font-medium"> {{ __($context) }} </span>
                    </nav>
                @endif

                <div class="flex-1" aria-live="polite" aria-atomic="false">{{ $slot }}</div>
            </div>
        </div>

        <x-slot:footer>
            <x-core::layouts.base.footer />
        </x-slot:footer>
    </x-ts-layout>

    <x-mary-spotlight />
</x-core::layouts.base>
