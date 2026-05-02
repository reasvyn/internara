@props(['title' => null, 'bodyClass' => 'bg-base-100'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <x-ui::layouts.base.head :$title />
    </head>

    <body class="{{ $bodyClass }} max-w-screen size-full overflow-x-hidden font-sans antialiased">
        <x-ui::layouts.base.preloader />

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
            {{ __('ui::common.skip_to_content') }}
        </a>

        <!-- Page Content --->
        <div class="flex size-full min-h-screen flex-col">
            {{ $slot }}
        </div>

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
                
                // Watch for theme changes from mary-theme-toggle
                new MutationObserver(syncFlasherTheme).observe(document.documentElement, {
                    attributes: true,
                    attributeFilter: ['data-theme']
                });
            });
        </script>

        @stack('scripts')
    </body>
</html>
