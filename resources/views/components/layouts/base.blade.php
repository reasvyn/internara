@props([
    'title' => null,
    'bodyClass' => 'max-w-screen size-full min-h-screen overflow-x-hidden antialiased',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <x-layouts.base.head :$title />
        @livewireStyles
    </head>

    <body class="{{ $bodyClass }}">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:p-4 focus:bg-base-100 focus:text-primary">
            Skip to content
        </a>

        <!-- Page Content -->
        {{ $slot }}

        <!-- Scripts -->
        @livewireScripts
        @stack('scripts')
    </body>
</html>
