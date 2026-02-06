@props([
    'title' => setting('site_title', setting('app_name')),
])

<!-- Meta Tags -->
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}">
@stack('meta')

<!-- Title -->
<title>{{ $title }}</title>

<!-- Favicon -->
<link rel="icon" href="/favicon.ico" sizes="any" />
<link rel="icon" href="/favicon.svg" type="image/svg+xml" />
<link rel="apple-touch-icon" href="/apple-touch-icon.png" />

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.bunny.net" />
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

<!-- Vite Assets -->
@vite(['resources/css/app.css', 'resources/js/app.js'])

@stack('head')
