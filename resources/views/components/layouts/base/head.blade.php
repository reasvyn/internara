@props([
    'title' => App\Support\Branding::siteTitle(),
])

@php
    $favicon = App\Support\Branding::favicon();
    $manifest = asset('/brand/site.webmanifest');
@endphp

<!-- Performance Hints -->
<link rel="preconnect" href="{{ config('app.url') }}" crossorigin>
<link rel="dns-prefetch" href="{{ config('app.url') }}">

<!-- Meta Tags -->
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
@stack('meta')

<!-- Title -->
<title>{{ $title }}</title>

<!-- Favicon -->
<link rel="icon" href="{{ $favicon }}" sizes="any" />
<link rel="apple-touch-icon" href="{{ $favicon }}" />

<link rel="manifest" href="{{ $manifest }}">

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.bunny.net" />
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

<!-- Vite Assets -->
@vite(['resources/css/app.css', 'resources/js/app.js'])

@stack('head')
