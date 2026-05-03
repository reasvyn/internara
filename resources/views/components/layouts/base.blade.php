@props([
    'title' => null,
    'bodyClass' => 'max-w-screen size-full min-h-screen overflow-x-hidden antialiased',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ request()->cookie('theme', 'system') }}">
    <head>
        <x-layouts.base.head :$title />
        
        {{-- Dynamic Branding Colors --}}
        @php
            $colors = brand('colors');
        @endphp
        <style>
            :root {
                --brand-primary: {{ $colors['primary'] }};
                --brand-secondary: {{ $colors['secondary'] }};
                --brand-accent: {{ $colors['accent'] }};
                
                /* Standard daisyUI variable mapping */
                --p: {{ $colors['primary'] }};
                --s: {{ $colors['secondary'] }};
                --a: {{ $colors['accent'] }};
            }
            
            [data-theme="dark"] {
                /* In dark mode, we soften the secondary and accent if they are too dark */
                --s: color-mix(in srgb, var(--brand-secondary), white 20%);
                --a: color-mix(in srgb, var(--brand-accent), white 20%);
            }
        </style>

        <script>
            // Theme initial application
            const theme = document.documentElement.getAttribute('data-theme');
            if (theme === 'system') {
                const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', systemTheme);
            }
        </script>
    </head>

    <body class="{{ $bodyClass }}">
        <x-layouts.base.preloader />

        <script>
            window.isDebugMode = window.isDebugMode || (() => {{ is_debug_mode() ? 'true' : 'false' }});
            window.isDevelopment = window.isDevelopment || (() => {{ is_development() ? 'true' : 'false' }});
            window.isTesting = window.isTesting || (() => {{ is_testing() ? 'true' : 'false' }});
            window.isMaintenance = window.isMaintenance || (() => {{ is_maintenance() ? 'true' : 'false' }});

            window.hidePreloader = window.hidePreloader || (() => {
                const preloader = document.getElementById('preloader');
                if (preloader && !preloader.classList.contains('opacity-0')) {
                    preloader.classList.add('opacity-0');
                    setTimeout(() => preloader.remove(), 500);
                }
            });

            // Handle initial load
            window.addEventListener('load', () => {
                window.hidePreloader();
            });

            // Handle Livewire SPA navigation
            document.addEventListener('livewire:navigated', () => {
                window.hidePreloader();
            });
        </script>

        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:p-4 focus:bg-base-100 focus:text-primary">
            Skip to content
        </a>

        <!-- Page Content -->
        {{ $slot }}

        @flasher_render

        <!-- Scripts -->
        @stack('scripts')

        <script>
            document.addEventListener('livewire:init', () => {
                // PHPFlasher Theme Sync
                const syncFlasherTheme = () => {
                    const theme = document.documentElement.getAttribute('data-theme');
                    if (theme === 'dark') {
                        document.documentElement.classList.add('fl-dark');
                    } else {
                        document.documentElement.classList.remove('fl-dark');
                    }
                };
                
                syncFlasherTheme();
                
                // Watch for theme changes from mary-theme-toggle or custom events
                new MutationObserver(syncFlasherTheme).observe(document.documentElement, {
                    attributes: true,
                    attributeFilter: ['data-theme']
                });

                // Listen for theme-changed event from Livewire
                Livewire.on('theme-changed', (event) => {
                    let newTheme = event.theme;
                    if (newTheme === 'system') {
                        newTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                    }
                    document.documentElement.setAttribute('data-theme', newTheme);
                });

                // Listen for language-changed event from Livewire
                Livewire.on('language-changed', () => {
                    window.location.reload();
                });
            });
        </script>
    </body>
</html>

