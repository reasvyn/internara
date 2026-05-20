@props(['title' => null])

<x-layouts::base :$title>
    <div class="min-h-screen flex flex-col bg-base-100">
        {{-- Header --}}
        <header class="border-b border-base-content/10">
            <div class="max-w-5xl mx-auto px-6 lg:px-12">
                <div class="flex items-center justify-between h-16">
                    <x-ui::brand size="sm" :invert="false" />

                    <div class="flex items-center gap-2">
                        <x-ui::theme-switcher class="px-2" />
                        <div class="w-px h-5 bg-base-content/10"></div>
                        <x-ui::lang-switcher class="px-2" />
                    </div>
                </div>
            </div>
        </header>

        {{-- Content --}}
        <main id="main-content" class="flex-1 flex flex-col py-8 sm:py-12">
            <div class="max-w-5xl mx-auto px-6 lg:px-12 w-full">
                {{ $slot }}
            </div>
        </main>

        {{-- Footer --}}
        <footer class="border-t border-base-content/10 py-6 mt-auto">
            <div class="max-w-5xl mx-auto px-6 lg:px-12">
                <div class="flex items-center justify-between gap-4">
                    <p class="text-xs text-base-content/40">
                        &copy; {{ date('Y') }} {{ brand('author.name') }}. {{ __('All rights reserved.') }}
                    </p>
                    <p class="text-xs text-base-content/30 font-mono">
                        v{{ app_info('version') }}
                    </p>
                </div>
            </div>
        </footer>
    </div>
</x-layouts::base>
