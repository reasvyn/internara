@props(['title' => null, 'footer' => null])

<x-ui::layouts.base :$title bodyClass="min-h-screen bg-base-200 flex flex-col antialiased">
    <main id="main-content" class="flex flex-1 items-center justify-center py-6 sm:py-10">
        <div class="w-full max-w-sm px-4 sm:max-w-md">
            <div class="mb-5 text-center sm:mb-6">
                <a
                    class="group inline-flex flex-col items-center gap-2.5"
                    href="{{ route('dashboard') }}"
                    wire:navigate
                >
                    <div class="bg-base-100 border-base-content/10 flex size-12 items-center justify-center rounded-xl border shadow-xs transition-transform group-hover:scale-105 sm:size-14">
                        <x-ui::components.logo size="7" />
                    </div>
                    <div class="flex flex-col items-center">
                        <span class="text-base-content group-hover:text-primary text-lg font-bold transition-colors sm:text-xl">
                            {{ brand('name') }}
                        </span>
                        <span class="text-base-content/40 text-[10px] font-medium tracking-wider uppercase">
                            {{ __('auth.title') }}
                        </span>
                    </div>
                </a>
            </div>

            {{ $slot }}

            <div class="mt-5 flex items-center justify-center gap-3 text-center">
                <div class="inline-flex"><x-setting::locale.theme-switch /></div>
                <livewire:setting.locale.lang-switch />
            </div>
        </div>
    </main>

    @isset($footer)
        {{ $footer }}
    @else
        <x-ui::layouts.base.footer compact />
    @endisset
</x-ui::layouts.base>
