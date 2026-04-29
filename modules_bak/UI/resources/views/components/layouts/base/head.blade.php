@props([
    'title' => setting('site_title', setting('app_name')),
])

@php
    $favicon = setting('site_favicon', setting('brand_logo'));
    $fallbackFavicon = asset('/brand/favicon.ico');
    $appleTouchIcon = asset('/brand/apple-touch-icon.png');
    $manifest = asset('/brand/site.webmanifest');
@endphp

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
@if($favicon)
    <link rel="icon" href="{{ $favicon }}" sizes="any" />
    <link rel="apple-touch-icon" href="{{ $favicon }}" />
@else
    <link rel="icon" href="{{ $fallbackFavicon }}" sizes="any" />
    <link rel="icon" href="{{ asset('/brand/favicon-32x32.png') }}" type="image/png" sizes="32x32" />
    <link rel="icon" href="{{ asset('/brand/favicon-16x16.png') }}" type="image/png" sizes="16x16" />
    <link rel="apple-touch-icon" href="{{ $appleTouchIcon }}" />
@endif

<link rel="manifest" href="{{ $manifest }}">

<!-- Vite Assets -->
@vite(['resources/css/app.css', 'resources/js/app.js'])

@stack('head')
