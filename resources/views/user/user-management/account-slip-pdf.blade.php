<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; padding: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            width: 241pt;
            height: 156pt;
            overflow: hidden;
        }
        .card {
            width: 241pt;
            height: 156pt;
            padding: 10pt 12pt;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 6pt;
            margin-bottom: 6pt;
        }
        .brand-name {
            font-size: 9pt;
            font-weight: 700;
            color: #1e40af;
            letter-spacing: 0.3pt;
        }
        .brand-tag {
            font-size: 5.5pt;
            color: #6b7280;
            letter-spacing: 0.5pt;
            text-transform: uppercase;
        }
        .divider {
            border: none;
            border-top: 0.5pt solid #e5e7eb;
            margin-bottom: 6pt;
        }
        .label {
            font-size: 5.5pt;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            margin-bottom: 1pt;
        }
        .value {
            font-size: 8pt;
            color: #111827;
            font-weight: 600;
            margin-bottom: 4pt;
        }
        .code-section {
            background: #f3f4f6;
            border-radius: 4pt;
            padding: 6pt 8pt;
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .code-label {
            font-size: 5pt;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.4pt;
        }
        .code-value {
            font-size: 14pt;
            font-weight: 800;
            color: #1e40af;
            letter-spacing: 2pt;
            font-family: 'DejaVu Sans Mono', monospace;
        }
        .footer {
            font-size: 4.5pt;
            color: #d1d5db;
            text-align: center;
            margin-top: 4pt;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand">
            <div>
                <div class="brand-name">{{ brand('name') }}</div>
                <div class="brand-tag">{{ __('sysadmin.account_slip.title') }}</div>
            </div>
        </div>

        <hr class="divider">

        <div class="label">{{ __('sysadmin.account_slip.name') }}</div>
        <div class="value">{{ $user->name }}</div>

        <div class="label">{{ __('sysadmin.account_slip.username') }}</div>
        <div class="value">{{ $user->username }}</div>

        <div class="label">{{ __('sysadmin.account_slip.email') }}</div>
        <div class="value">{{ $user->email }}</div>

        <div class="code-section">
            <div>
                <div class="code-label">{{ __('sysadmin.account_slip.activation_code') }}</div>
                <div class="code-value">{{ $code }}</div>
            </div>
        </div>

        <div class="footer">{{ __('sysadmin.account_slip.instruction') }}</div>
    </div>
</body>
</html>
