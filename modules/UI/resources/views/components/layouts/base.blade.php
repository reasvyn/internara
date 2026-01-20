@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <x-ui::layouts.base.head :$title />
    </head>

    <body class="max-w-screen size-full overflow-x-hidden font-sans antialiased">
        <!-- Page Content --->
        <div class="flex size-full min-h-screen flex-col">
            {{ $slot }}
        </div>

        <!-- Toast Area --->
        <div class="fixed z-20">
            <x-mary-toast />
        </div>

        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.on('notify', (data) => {
                    const payload = Array.isArray(data) ? data[0] : data;
                    
                    Livewire.dispatch('toast', {
                        type: payload.type || 'info',
                        title: payload.type === 'error' ? '{{ __("Error") }}' : '{{ __("Success") }}',
                        description: payload.message,
                        icon: payload.type === 'error' ? 'tabler.alert-circle' : 'tabler.check'
                    });
                });
            });
        </script>

        @stack('scripts')
    </body>
</html>
