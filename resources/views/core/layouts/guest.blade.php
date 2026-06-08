@props (['title' => null, 'header' => null, 'footer' => null])

<x-core::layouts.base :$title>
    <div class="min-h-screen flex flex-col bg-base-200">
        <header
            class="bg-base-100/80 backdrop-blur-sm border-b border-base-content/10 sticky top-0 z-50"
        >
            <div class="container mx-auto px-6 lg:px-12">
                <div class="flex items-center justify-between h-16">
                    <a wire:navigate href="/" class="flex items-center gap-3">
                        <x-core::ui.brand size="sm" :invert="false" />
                    </a>

                    <div class="flex items-center gap-3">
                        <livewire:settings.theme-switcher />
                        <livewire:settings.lang-switcher />
                    </div>
                </div>
            </div>
        </header>

        <main id="main-content" class="flex-1 flex flex-col">{{ $slot }}</main>

        <footer class="border-t border-base-content/10 py-8 mt-auto">
            <div class="container mx-auto px-6 text-center">
                @isset ($footer)
                    {{ $footer }}
                @else
                    <x-core::ui.credits />
                @endisset
            </div>
        </footer>
    </div>
</x-core::layouts.base>
