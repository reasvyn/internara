@props([
    'title' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'Internara') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>

    <body class="h-full font-sans antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center bg-base-200 px-4 py-12 sm:px-6 lg:px-8">
            <div class="w-full max-w-md space-y-8">
                <!-- Logo / Brand -->
                <div class="text-center">
                    <h1 class="text-3xl font-bold text-base-content">{{ config('app.name', 'Internara') }}</h1>
                    @if ($title)
                        <p class="mt-2 text-sm text-base-content/60">{{ $title }}</p>
                    @endif
                </div>

                <!-- Page Content -->
                {{ $slot }}

                <!-- Author Signature -->
                <livewire:layout.app-signature />
            </div>
        </div>

        @livewireScripts
        @stack('scripts')
    </body>
</html>
