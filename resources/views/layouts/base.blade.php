@props([
    'title' => null,
    'bodyClass' => 'max-w-screen size-full min-h-screen overflow-x-hidden antialiased',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ request()->cookie('theme', 'system') }}">
    <head>
        <x-layouts::base.head :$title />
        
        {{-- Dynamic Branding Colors --}}
        @php
            use App\Support\BrandColors;
            $themeVars = BrandColors::cssVariables();
        @endphp
        <style>
            html[data-theme="light"],
            html:not([data-theme="dark"]) {
                @foreach ($themeVars['light'] as $var => $value)
                    {{ $var }}: {{ $value }};
                @endforeach
            }

            html[data-theme="dark"] {
                @foreach ($themeVars['dark'] as $var => $value)
                    {{ $var }}: {{ $value }};
                @endforeach
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
        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:p-4 focus:bg-base-100 focus:text-base-content">
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
