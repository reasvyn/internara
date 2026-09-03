@props(['title' => null, 'header' => null, 'footer' => null])

<x-ui::layouts.base :$title>
    <div class="bg-base-200 flex min-h-screen flex-col">
        <header class="bg-base-100/80 border-base-content/10 sticky top-0 z-50 border-b backdrop-blur-sm">
            <div class="container mx-auto px-6 lg:px-12">
                <div class="flex h-16 items-center justify-between">
                    <a wire:navigate href="/" class="flex items-center gap-3">
                        <x-ui::components.brand size="sm" :invert="false" />
                    </a>

                    <div class="flex items-center gap-3">
                        <div class="inline-flex">
                            <x-ui::components.theme-switch />
                        </div>
                        <livewire:settings.lang-switcher />
                    </div>
                </div>
            </div>
        </header>

        <main id="main-content" class="flex flex-1 flex-col">{{ $slot }}</main>

        <footer class="border-base-content/10 mt-auto border-t py-8">
            <div class="container mx-auto px-6 text-center">
                @isset($footer)
                    {{ $footer }}
                @else
                    <x-ui::components.credits />
                @endisset
            </div>
        </footer>
    </div>
</x-ui::layouts.base>
