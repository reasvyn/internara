@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <x-ui::layouts.base.head :$title />
    </head>

    <body class="max-w-screen size-full overflow-x-hidden font-sans antialiased">
        <x-ui::layouts.base.preloader />

        <script>
            window.hidePreloader = window.hidePreloader || (() => {
                const preloader = document.getElementById('preloader');
                if (preloader && !preloader.classList.contains('opacity-0')) {
                    preloader.classList.add('opacity-0');
                    setTimeout(() => preloader.remove(), 500);
                }
            });

            window.triggerNotify = window.triggerNotify || ((payload) => {
                if (!payload) return;

                const notify = (item) => {
                    const data = {
                        message: item.message || item.description || (typeof item === 'string' ? item : ''),
                        type: item.type || 'info',
                        title: item.title || null,
                        timeout: item.timeout || 5000,
                        autohide: item.autohide !== undefined ? item.autohide : true
                    };
                    window.dispatchEvent(new CustomEvent('notify', { detail: data }));
                };

                if (Array.isArray(payload)) {
                    payload.forEach(item => notify(item));
                } else {
                    notify(payload);
                }
            });

            // Handle initial load (waits for all assets: images, fonts, etc.)
            window.addEventListener('load', window.hidePreloader);

            // Handle Livewire SPA navigation
            document.addEventListener('livewire:navigated', window.hidePreloader);
        </script>

        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:p-4 focus:bg-base-100 focus:text-primary">
            {{ __('ui::common.skip_to_content') }}
        </a>

        <!-- Page Content --->
        <div class="flex size-full min-h-screen flex-col">
            {{ $slot }}
        </div>

        <livewire:ui::notification-bridge />
        <x-ui::toast />

        <script>
            document.addEventListener('livewire:init', () => {
                console.log('Internara Notification System: Initialized');
                
                // Handle Initial Session Flash
                const sessionNotify = @json(session()->pull('notify'));
                if (sessionNotify) {
                    window.triggerNotify(sessionNotify);
                }
            });
        </script>

        @stack('scripts')
    </body>
</html>
