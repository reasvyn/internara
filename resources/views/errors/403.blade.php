<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@lang('Unauthorized') - {{ config('app.name', 'Internara') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-base-200 min-h-screen flex items-center justify-center">
    <div class="text-center p-8">
        <div class="mb-6">
            <div class="avatar placeholder">
                <div class="bg-error text-error-content rounded-full w-24">
                    <x-mary-icon name="o-shield-exclamation" class="w-16 h-16" />
                </div>
            </div>
        </div>
        <h1 class="text-4xl font-bold text-error mb-4">403</h1>
        <h2 class="text-2xl font-semibold mb-4">@lang('Unauthorized')</h2>
        <p class="text-base-content/70 mb-8 max-w-md mx-auto">
            {{ $exception->getMessage() ?: __('You do not have the required role to access this resource.') }}
        </p>
        <div class="flex justify-center gap-4">
            <a href="{{ url()->previous() }}" class="btn btn-outline">@lang('Go Back')</a>
            <a href="{{ route('login') }}" class="btn btn-primary">@lang('Go to Login')</a>
        </div>
    </div>
</body>
</html>
