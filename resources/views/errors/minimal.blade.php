<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title') — {{ config('app.name') }}</title>
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
                background: #f8fafc;
                color: #1e293b;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem;
            }
            .container { text-align: center; max-width: 32rem; }
            .code { font-size: 5rem; font-weight: 800; color: #94a3b8; line-height: 1; }
            .message { margin-top: 1rem; font-size: 1.125rem; color: #475569; }
            .brand { margin-top: 2rem; font-size: 0.875rem; color: #94a3b8; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="code">@yield('code')</div>
            <div class="message">@yield('message')</div>
            <div class="brand">{{ config('app.name') }}</div>
        </div>
    </body>
</html>
