<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8" />
    <title>{{ __('sysadmin.account_slip.title') }} — {{ $user->name }}</title>
    <style>
        body {
            font-family:
                DejaVu Sans,
                sans-serif;
            color: #111;
        }
        .label {
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #666;
        }
        .mono {
            font-family:
                DejaVu Sans Mono,
                monospace;
        }
        .code {
            font-family:
                DejaVu Sans Mono,
                monospace;
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 4px;
        }
        .card {
            border: 1px solid #ccc;
            padding: 16px;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }}</h1>
    <h2>{{ $user->name }}</h2>

    <div class="card">
        <p class="label text-xs font-semibold tracking-wider uppercase">{{ __('sysadmin.account_slip.name') }}</p>
        <p>{{ $user->name }}</p>

        <p class="label text-xs font-semibold tracking-wider uppercase">{{ __('sysadmin.account_slip.username') }}</p>
        <p class="mono font-mono text-sm">{{ $user->username }}</p>

        <p class="label text-xs font-semibold tracking-wider uppercase">{{ __('sysadmin.account_slip.email') }}</p>
        <p>{{ $user->email }}</p>
    </div>

    <div class="card">
        <p class="label text-xs font-semibold tracking-wider uppercase">
            {{ __('sysadmin.account_slip.activation_code') }}
        </p>
        <p class="code font-mono select-all">{{ $code }}</p>
        <p>{{ __('sysadmin.account_slip.code_expiry', ['days' => 30]) }}</p>
        <p>{{ __('sysadmin.account_slip.instruction') }}</p>
    </div>
</body>
</html>
