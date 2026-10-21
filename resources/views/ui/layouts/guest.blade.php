@props(['title' => null, 'header' => null, 'footer' => null])

<x-ui::layouts.base :$title>
    <div class="bg-base-200/50 flex min-h-screen flex-col">
        <header class="border-base-content/10 bg-base-100/80 sticky top-0 z-50 border-b backdrop-blur-sm">
            <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-14 items-center justify-between sm:h-16">
                    <a wire:navigate href="/" class="flex items-center gap-3">
                        <x-ui::components.brand size="sm" :invert="false" />
                    </a>

                    <div class="flex items-center gap-3 sm:gap-4">
                        <a
                            wire:navigate
                            href="{{ route('about') }}"
                            class="text-base-content/70 hover:text-primary text-xs font-medium transition-colors sm:text-sm"
                        >
                            {{ __('about.title') }}
                        </a>

                        <div class="inline-flex">
                            <x-setting::locale.theme-switch />
                        </div>
                        <livewire:setting.locale.lang-switch />

                        @if (Route::has('login'))
                            @auth
                                <a
                                    wire:navigate
                                    href="{{ route('dashboard') }}"
                                    class="border-primary bg-primary text-primary-content hover:bg-primary/80 hidden rounded-lg border px-3 py-1.5 text-xs font-semibold shadow-xs transition-colors sm:inline-flex"
                                >
                                    {{ __('user.dashboard.title') }}
                                </a>
                            @else
                                <a
                                    wire:navigate
                                    href="{{ route('login') }}"
                                    class="border-primary bg-primary text-primary-content hover:bg-primary/80 hidden rounded-lg border px-3 py-1.5 text-xs font-semibold shadow-xs transition-colors sm:inline-flex"
                                >
                                    {{ __('user.home.login_action') }}
                                </a>
                            @endauth
                        @endif
                    </div>
                </div>
            </div>
        </header>

        <main id="main-content" class="flex flex-1 flex-col">{{ $slot }}</main>

        <footer class="border-base-content/10 mt-auto border-t py-5 sm:py-6">
            <div class="mx-auto w-full max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                @isset($footer)
                    {{ $footer }}
                @else
                    <div class="flex flex-col items-center gap-4">
                        <nav
                            aria-label="{{ __('common.footer_navigation') }}"
                            class="text-base-content/60 flex items-center gap-6 text-xs"
                        >
                            <a wire:navigate href="{{ route('about') }}" class="hover:text-primary transition-colors">
                                {{ __('about.title') }}
                            </a>
                        </nav>
                        <x-ui::components.credits />
                    </div>
                @endisset
            </div>
        </footer>
    </div>
</x-ui::layouts.base>
