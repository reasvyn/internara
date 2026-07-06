@props(['title' => null])

<x-core::layouts.base :$title bodyClass="min-h-screen bg-base-200 flex items-center justify-center py-8">
    <div class="w-full max-w-sm px-4">
        <div class="mb-8 text-center">
            <a class="group inline-flex flex-col items-center gap-3" href="{{ route('dashboard') }}" wire:navigate>
                <div
                    class="bg-base-200 flex size-14 items-center justify-center rounded-xl transition-transform group-hover:scale-105">
                    <x-core::ui.logo size="8" />
                </div>
                <div class="flex flex-col items-center">
                    <span class="text-base-content group-hover:text-primary text-xl font-bold transition-colors">
                        {{ brand('name') }}
                    </span>
                    <span class="text-base-content/40 text-[10px] font-medium uppercase tracking-wider">
                        {{ __('auth.title') }}
                    </span>
                </div>
            </a>
        </div>

        {{ $slot }}

        <div class="mt-8 flex items-center justify-center gap-3 text-center">
            <livewire:settings.theme-switcher />
            <livewire:settings.lang-switcher />
        </div>
    </div>
</x-core::layouts.base>
