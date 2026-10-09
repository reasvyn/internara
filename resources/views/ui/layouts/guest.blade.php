@props(['title' => null, 'header' => null, 'footer' => null])

<x-ui::layouts.base :$title>
    <div class="dark:bg-dark-900 flex min-h-screen flex-col bg-gray-50">
        <header class="dark:border-dark-700 dark:bg-dark-800/80 sticky top-0 z-50 border-b border-gray-200 bg-white/80 backdrop-blur-sm">
            <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <a wire:navigate href="/" class="flex items-center gap-3">
                        <x-ui::components.brand size="sm" :invert="false" />
                    </a>

                    <div class="flex items-center gap-3">
                        <div class="inline-flex">
                            <x-setting::locale.theme-switch />
                        </div>
                        <livewire:setting.locale.lang-switch />
                    </div>
                </div>
            </div>
        </header>

        <main id="main-content" class="flex flex-1 flex-col">{{ $slot }}</main>

        <footer class="dark:border-dark-700 mt-auto border-t border-gray-200 py-8">
            <div class="mx-auto w-full max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                @isset($footer)
                    {{ $footer }}
                @else
                    <x-ui::components.credits />
                @endisset
            </div>
        </footer>
    </div>
</x-ui::layouts.base>
