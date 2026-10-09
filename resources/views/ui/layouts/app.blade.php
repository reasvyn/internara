@props([
    'title' => null,
    'header' => null,
    'footer' => null,
    'context' => null,
])

<x-ui::layouts.base :$title>
    <x-ts-layout>
        <x-slot:menu>
            <x-ui::layouts.sidebar />
        </x-slot:menu>

        <x-slot:header>
            <x-ui::layouts.header :$header />
        </x-slot:header>

        <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col">
            @if ($context)
                <nav
                    aria-label="Breadcrumb"
                    class="dark:text-dark-400 mb-5 flex items-center gap-2 text-xs text-gray-500"
                >
                    <a wire:navigate href="{{ route('dashboard') }}" class="hover:text-primary transition-colors">
                        {{ brand('name') }}
                    </a>
                    <span class="opacity-40">/</span>
                    <span class="text-primary font-medium"> {{ __($context) }} </span>
                </nav>
            @endif

            <div class="flex-1" aria-live="polite" aria-atomic="false">{{ $slot }}</div>
        </div>

        <x-slot:footer>
            <x-ui::layouts.base.footer />
        </x-slot:footer>
    </x-ts-layout>
</x-ui::layouts.base>
