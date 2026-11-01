@props(['title' => null])
@php
    $mcpSdk = app('mcp.sdk');
    $libraryScripts = app()->bound('mcp.library_scripts') ? app('mcp.library_scripts') : '';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' - '.brand('name') : brand('title') }}</title>
    <script>{!! $mcpSdk !!}</script>
    {!! $libraryScripts !!}
    {{ $head ?? '' }}
</head>
<body {{ $attributes }}>
    {{ $slot }}
</body>
</html>
