@props(['title' => null])

<x-core::layouts.base :$title>
    <div class="bg-base-100 flex min-h-screen flex-col" x-data x-init="window.addEventListener('beforeunload', () => {
        navigator.sendBeacon(@js(route('setup.cleanup')));
    });">
        {{-- Header --}}
        <header class="border-base-content/10 border-b">
            <div class="mx-auto max-w-5xl px-6 lg:px-12">
                <div class="flex h-16 items-center justify-between">
                    <x-core::ui.brand size="sm" :invert="false" />

                    <div class="flex items-center gap-2">
                        <livewire:settings.livewire.theme-switcher class="px-2" />
                        <div class="bg-base-content/10 h-5 w-px"></div>
                        <livewire:settings.livewire.lang-switcher class="px-2" />
                    </div>
                </div>
            </div>
        </header>

        {{-- Content --}}
        <main class="flex flex-1 flex-col py-8 sm:py-12" id="main-content">
            <div class="mx-auto w-full max-w-5xl px-6 lg:px-12">{{ $slot }}</div>
        </main>

        {{-- Footer --}}
        <footer class="border-base-content/10 mt-auto border-t py-6">
            <div class="mx-auto max-w-5xl px-6 lg:px-12">
                <div class="flex items-center justify-between gap-4">
                    <p class="text-base-content/40 text-xs">
                        &copy; {{ date('Y') }} {{ brand('author.name') }}. {{ __('All rights reserved.') }}
                    </p>
                    <p class="text-base-content/30 font-mono text-xs">v{{ app_info('version') }}</p>
                </div>
            </div>
        </footer>
    </div>
</x-core::layouts.base>
