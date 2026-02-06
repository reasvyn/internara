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

        <!-- Toast Area --->
        <x-ui::toast class="toast-bottom toast-end z-50" />

        <script>
            document.addEventListener('livewire:init', () => {
                const triggerToast = (payload) => {
                    const type = payload.type || 'info';
                    if (typeof window.toast === 'function') {
                        window.toast({
                            toast: {
                                type: type,
                                title: type.charAt(0).toUpperCase() + type.slice(1),
                                description: payload.message,
                                css: `alert-${type}`,
                                timeout: payload.options?.timeout || 3000,
                            }
                        });
                    }
                };

                // Listen for Livewire events
                Livewire.on('notify', (data) => {
                    const payload = Array.isArray(data) ? data[0] : data;
                    triggerToast(payload);
                });

                // Handle session flashed notifications
                @if(session('notify'))
                    triggerToast(@json(session('notify')));
                @endif
            });
        </script>

        @stack('scripts')
    </body>
</html>
