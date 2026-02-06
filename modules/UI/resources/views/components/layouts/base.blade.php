@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <x-ui::layouts.base.head :$title />
    </head>

    <body class="max-w-screen size-full overflow-x-hidden font-sans antialiased">
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
                const triggerNotify = (payload) => {
                    window.dispatchEvent(new CustomEvent('notify', { detail: payload }));
                };

                // Real-time Browser Events (from NotificationBridge)
                Livewire.on('notify-browser', (data) => {
                    const payload = Array.isArray(data) ? data[0] : data;
                    triggerNotify(payload);
                });

                // Initial Session Flash (from Standard Redirects)
                @if(session('notify'))
                    triggerNotify(@json(session('notify')));
                @endif
            });
        </script>

        @stack('scripts')
    </body>
</html>
