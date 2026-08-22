@props([
    'title' => null,
    'bodyClass' => 'max-w-screen size-full min-h-screen overflow-x-hidden antialiased',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ request()->cookie('theme', 'system') }}">
<head>
    <x-core::layouts.base.head :$title />

    {{-- Dynamic Branding Colors --}}
    @php
        use App\Settings\Theme\Support\Theme;

        $themeVars = Theme::cssVariables();
    @endphp
    <style>
        html[data-theme='light'],
        html:not([data-theme='dark']) {
            @foreach ($themeVars['light'] as $var => $value)
                    {{ $var }}: {{ $value }};
                @endforeach
        }

        html[data-theme='dark'] {
            @foreach ($themeVars['dark'] as $var => $value)
                    {{ $var }}: {{ $value }};
                @endforeach
        }
    </style>
</head>

<body class="{{ $bodyClass }}">
    <a
        href="#main-content"
        class="focus:bg-base-100 focus:text-base-content sr-only focus:not-sr-only focus:absolute focus:z-50 focus:p-4"
    >
        Skip to content
    </a>

    <!-- Page Content --> {{ $slot }}

    @flasher_render

    <!-- Scripts -->
    @stack('scripts')
</body>
</html>
