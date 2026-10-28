@props(['title' => null])

<x-ui::layouts.base :$title>
    <div class="bg-base-200/60 text-base-content selection:bg-primary/20 selection:text-primary relative flex min-h-screen flex-col">
        {{-- Ambient decorative background glow --}}
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
            <div class="bg-primary/5 absolute -top-40 left-1/2 size-[700px] -translate-x-1/2 rounded-full blur-3xl"></div>
            <div class="bg-primary/3 absolute top-1/2 -right-40 size-[500px] rounded-full blur-3xl"></div>
        </div>

        {{-- Header --}}
        <header class="border-base-content/10 bg-base-100/80 sticky top-0 z-30 border-b backdrop-blur-md transition-all">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-13 items-center justify-between sm:h-14">
                    <x-ui::components.brand size="sm" :invert="false" />

                    <div class="flex items-center gap-2">
                        <div class="border-base-content/10 bg-base-100/90 flex items-center gap-1.5 rounded-md border px-2.5 py-0.5 shadow-xs sm:py-1">
                            <x-setting::locale.theme-switch size="xs" />
                            <div class="bg-base-content/10 h-3.5 w-px sm:h-4"></div>
                            <livewire:setting.locale.lang-switch class="px-1" />
                        </div>
                    </div>
                </div>
            </div>
        </header>

        {{-- Content --}}
        <main class="flex flex-1 flex-col justify-center py-4 sm:py-6 lg:py-8" id="main-content">
            <div class="mx-auto w-full max-w-4xl px-4 sm:px-6 lg:px-8">{{ $slot }}</div>
        </main>

        {{-- Footer --}}
        <footer class="border-base-content/10 bg-base-100/50 mt-auto border-t py-2.5 backdrop-blur-xs sm:py-3">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center justify-between gap-2 text-center sm:flex-row sm:text-left">
                    <p class="text-base-content/50 text-xs">
                        &copy; {{ date('Y') }} {{ brand('author.name') }}. {{ __('All rights reserved.') }}
                    </p>
                    <div class="flex items-center gap-2">
                        <span class="border-base-content/10 bg-base-100 text-base-content/70 inline-flex items-center gap-1.5 rounded-md border px-2.5 py-0.5 font-mono text-[11px] shadow-xs">
                            <span class="bg-success size-1.5 rounded-full"></span>
                            v{{ app_info('version') }}
                        </span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</x-ui::layouts.base>
