@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <x-ui::layouts.base.head :$title />
    </head>

    <body class="max-w-screen size-full overflow-x-hidden font-sans antialiased">
        <x-ui::layouts.base.preloader />

        <script>
            const hidePreloader = () => {
                const preloader = document.getElementById('preloader');
                if (preloader && !preloader.classList.contains('opacity-0')) {
                    preloader.classList.add('opacity-0');
                    setTimeout(() => preloader.remove(), 500);
                }
            };

            // Handle initial load (waits for all assets: images, fonts, etc.)
            window.addEventListener('load', hidePreloader);

            // Handle Livewire SPA navigation
            document.addEventListener('livewire:navigated', hidePreloader);
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
            window.hidePreloader = window.hidePreloader || (() => {
                const preloader = document.getElementById('preloader');
                if (preloader && !preloader.classList.contains('opacity-0')) {
                    preloader.classList.add('opacity-0');
                    setTimeout(() => preloader.remove(), 500);
                }
            });

            window.triggerNotify = window.triggerNotify || ((payload) => {
                if (!payload) return;
                const data = {
                    message: payload.message || payload.description || (typeof payload === 'string' ? payload : ''),
                    type: payload.type || 'info',
                    title: payload.title || null,
                    timeout: payload.timeout || 5000,
                    autohide: payload.autohide !== undefined ? payload.autohide : true
                };
                window.dispatchEvent(new CustomEvent('notify', { detail: data }));
            });

            window.addEventListener('load', window.hidePreloader);
            document.addEventListener('livewire:navigated', window.hidePreloader);

            document.addEventListener('livewire:init', () => {
                console.log('Internara Notification System: Initialized');
                
                // Handle Initial Session Flash
                @if(session('notify'))
                    window.triggerNotify(@json(session('notify')));
                @endif
            });

            document.addEventListener('livewire:navigated', () => {
                // Handle Session Flash on SPA Navigation
                @if(session('notify'))
                    window.triggerNotify(@json(session('notify')));
                @endif
            });
        </script>

        @stack('scripts')
    </body>
</html>
