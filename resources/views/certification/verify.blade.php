<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-base-200 h-full">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ __('certificate.verify.title') }} - {{ brand('name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="text-base-content flex h-full flex-col items-center justify-center p-4 antialiased">
    <div class="border-base-content/10 bg-base-100 w-full max-w-md overflow-hidden rounded-xl border shadow-lg">
        <div class="border-base-content/10 border-b p-6 text-center">
            <h1 class="text-xl font-bold tracking-tight">{{ __('certificate.verify.title') }}</h1>
            <p class="text-base-content/60 mt-1 text-sm">{{ __('certificate.verify.subtitle') }}</p>
        </div>

        <div class="space-y-6 p-6">
            @if ($verification->status === 'issued')
                <div class="border-success/30 bg-success/10 text-success flex items-center gap-3 rounded-xl border p-4">
                    <span class="bg-success/20 inline-flex h-8 w-8 items-center justify-center rounded-full font-bold">✓</span>
                    <div>
                        <div class="text-sm font-semibold">{{ __('certificate.verify.status_issued') }}</div>
                        <div class="mt-0.5 text-xs opacity-90">{{ $verification->schoolName }}</div>
                    </div>
                </div>
            @elseif ($verification->status === 'revoked')
                <div class="border-warning/30 bg-warning/10 text-warning flex items-center gap-3 rounded-xl border p-4">
                    <span class="bg-warning/20 inline-flex h-8 w-8 items-center justify-center rounded-full font-bold">!</span>
                    <div>
                        <div class="text-sm font-semibold">{{ __('certificate.verify.status_revoked') }}</div>
                        <div class="mt-0.5 text-xs opacity-90">{{ __('certificate.verify.revoked_warning') }}</div>
                    </div>
                </div>
            @else
                <div class="border-error/30 bg-error/10 text-error flex items-center gap-3 rounded-xl border p-4">
                    <span class="bg-error/20 inline-flex h-8 w-8 items-center justify-center rounded-full font-bold">✕</span>
                    <div>
                        <div class="text-sm font-semibold">{{ __('certificate.verify.status_not_found') }}</div>
                        <div class="mt-0.5 text-xs opacity-90">{{ __('certificate.verify.not_found_message') }}</div>
                    </div>
                </div>
            @endif

            @if ($verification->status !== 'not_found')
                <dl class="divide-base-content/10 divide-y text-sm">
                    <div class="flex justify-between py-2.5">
                        <dt class="text-base-content/60">{{ __('certificate.verify.holder') }}</dt>
                        <dd class="text-right font-medium">{{ $verification->studentName }}</dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-base-content/60">{{ __('certificate.verify.school') }}</dt>
                        <dd class="text-right font-medium">{{ $verification->schoolName }}</dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-base-content/60">{{ __('certificate.verify.number') }}</dt>
                        <dd class="text-right font-mono text-xs font-semibold">
                            {{ $verification->certificateNumber }}
                        </dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-base-content/60">{{ __('certificate.verify.issued_at') }}</dt>
                        <dd class="text-right font-medium">{{ $verification->issuedAt }}</dd>
                    </div>
                    @if ($verification->revokedAt)
                        <div class="text-warning flex justify-between py-2.5">
                            <dt>{{ __('certificate.verify.revoked_at') }}</dt>
                            <dd class="text-right font-medium">{{ $verification->revokedAt }}</dd>
                        </div>
                    @endif
                </dl>
            @endif
        </div>

        <div class="border-base-content/10 bg-base-200/50 border-t px-6 py-4 text-center">
            <span class="text-base-content/50 text-xs"> {{ config('app.name') }} &bull; {{ date('Y') }} </span>
        </div>
    </div>
</body>
</html>
