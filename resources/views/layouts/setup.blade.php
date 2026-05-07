@props(['title' => null])

<x-layouts.base :$title>
    <div class="min-h-screen flex flex-col bg-base-100">
        {{-- Header --}}
        <header class="border-b border-base-content/5">
            <div class="container mx-auto px-4 sm:px-6 lg:px-12">
                <div class="flex items-center justify-between h-14 sm:h-16">
                    <x-ui.brand :invert="false" />

                    <div class="flex items-center gap-2 sm:gap-3">
                        <div class="hidden md:flex items-center gap-1 mr-2 sm:mr-4 bg-base-200/50 p-1 rounded-lg border border-base-content/5">
                            <livewire:core.theme-switcher />
                        </div>
                        <livewire:core.language-switcher />
                    </div>
                </div>
            </div>
        </header>

        {{-- Main Content --}}
        <main id="main-content" class="flex-1 flex flex-col py-8 sm:py-12 lg:py-16">
            {{ $slot }}
        </main>

        {{-- Footer --}}
        <footer class="border-t border-base-content/5 py-4 sm:py-6 mt-auto">
            <div class="container mx-auto px-4 sm:px-6 text-center">
                <p class="text-[9px] sm:text-[10px] uppercase font-medium tracking-wider opacity-30">
                    {{ trans('common.app_tagline') }}
                </p>
            </div>
        </footer>
    </div>
</x-layouts.base>
