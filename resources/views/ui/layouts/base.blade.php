@props([
    'title' => null,
    'bodyClass' => 'text-base-content bg-base-100 dark:bg-dark-900 max-w-screen size-full min-h-screen overflow-x-hidden antialiased',
])

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-theme="{{ request()->cookie('theme', 'system') }}"
    @if (request()->cookie('theme') === 'dark') class="dark" @endif
>
<head>
    <x-ui::layouts.base.head :$title />

    {{-- Dynamic Branding Colors --}}
    @php
        use App\Modules\Settings\Domain\Theme\Support\Theme;

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
        class="focus:ring-primary-500 dark:focus:bg-dark-800 sr-only focus:not-sr-only focus:absolute focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-gray-900 focus:ring-2 focus:ring-offset-2 dark:focus:text-white"
    >
        {{ __('common.skip_to_content') }}
    </a>

    <!-- Page Content --> {{ $slot }}

    <x-ts-toast />
    <x-ts-dialog />

    <!-- Scripts -->
    @stack('scripts')
</body>
</html>
