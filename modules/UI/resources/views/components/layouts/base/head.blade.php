@props([
    'title' => setting('site_title', setting('app_name')),
])

<!-- Performance Hints -->
<link rel="preconnect" href="{{ config('app.url') }}" crossorigin>
<link rel="dns-prefetch" href="{{ config('app.url') }}">

<!-- Meta Tags -->
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}">
@stack('meta')

<!-- Title -->
<title>{{ $title }}</title>

<!-- Favicon -->
<link rel="icon" href="{{ asset('/internara/favicon.ico') }}" sizes="any" />
<link rel="icon" href="{{ asset('/internara/favicon-32x32.png') }}" type="image/png" sizes="32x32" />
<link rel="icon" href="{{ asset('/internara/favicon-16x16.png') }}" type="image/png" sizes="16x16" />
<link rel="apple-touch-icon" href="{{ asset('/internara/apple-touch-icon.png') }}" />
<link rel="manifest" href="{{ asset('/internara/site.webmanifest') }}">

<!-- Vite Assets -->
@vite(['resources/css/app.css', 'resources/js/app.js'])

@stack('head')
