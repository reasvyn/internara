<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('profile.recovery.title') }}</title>
    <style>
        body { font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.6; color: #1a1a1a; }
        .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #e5e5e5; }
        .header h1 { font-size: 20px; margin: 0 0 5px; }
        .header p { color: #666; margin: 0; font-size: 11px; }
        .codes { margin: 30px 0; }
        .code-row { padding: 10px 15px; margin-bottom: 8px; border: 1px solid #e5e5e5; border-radius: 4px; font-size: 14px; font-weight: bold; letter-spacing: 2px; }
        .code-row .num { color: #999; font-weight: normal; letter-spacing: normal; margin-right: 10px; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #e5e5e5; font-size: 10px; color: #999; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 12px; margin: 20px 0; font-size: 11px; }
        .warning strong { color: #856404; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('profile.recovery.recovery_codes') }}</h1>
        <p>{{ $username }}</p>
    </div>

    <div class="warning">
        <strong>&#9888; {{ __('profile.recovery.important') }}</strong><br>
        {{ __('profile.recovery.generated_desc') }}<br>
        {{ __('profile.recovery.store_securely') }}
    </div>

    <div class="codes">
        @foreach($codes as $index => $code)
            <div class="code-row">
                <span class="num">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}.</span>
                {{ $code }}
            </div>
        @endforeach
    </div>

    <div class="footer">
        <p>{{ __('profile.recovery.generated_at', ['date' => $generatedAt]) }}</p>
    </div>
</body>
</html>
