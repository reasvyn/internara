@props(['title' => null])

<x-layouts::base :$title bodyClass="min-h-screen bg-base-200 flex items-center justify-center py-8">
    <div class="w-full max-w-sm px-4">
        <div class="text-center mb-8">
            <a href="{{ route('dashboard') }}" class="inline-flex flex-col items-center gap-3 group" wire:navigate>
                <div class="size-14 rounded-xl bg-base-200 flex items-center justify-center transition-transform group-hover:scale-105">
                    <x-ui::logo size="8" />
                </div>
                <div class="flex flex-col items-center">
                    <span class="text-xl font-bold text-base-content group-hover:text-primary transition-colors">
                        {{ brand('name') }}
                    </span>
                    <span class="text-[10px] font-medium uppercase tracking-wider text-base-content/40">
                        {{ __('auth.title') }}
                    </span>
                </div>
            </a>
        </div>

        {{ $slot }}

        <div class="mt-8 text-center flex items-center justify-center gap-3">
            <x-ui::theme-switcher />
            <x-ui::lang-switcher />
        </div>
    </div>
</x-layouts::base>
