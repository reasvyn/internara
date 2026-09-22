<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 dark:bg-slate-900">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ __('certificate.verify.title') }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex h-full flex-col items-center justify-center p-4 text-slate-800 antialiased dark:text-slate-100">
    <div class="w-full max-w-md overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-800">
        <div class="border-b border-slate-100 p-6 text-center dark:border-slate-700/60">
            <h1 class="text-xl font-bold tracking-tight">{{ __('certificate.verify.title') }}</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('certificate.verify.subtitle') }}</p>
        </div>

        <div class="space-y-6 p-6">
            @if ($verification->status === 'issued')
                <div class="flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-200">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 font-bold text-emerald-600 dark:bg-emerald-900 dark:text-emerald-300">✓</span>
                    <div>
                        <div class="text-sm font-semibold">{{ __('certificate.verify.status_issued') }}</div>
                        <div class="mt-0.5 text-xs text-emerald-700 dark:text-emerald-400">
                            {{ $verification->schoolName }}
                        </div>
                    </div>
                </div>
            @elseif ($verification->status === 'revoked')
                <div class="flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-800 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-200">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 font-bold text-amber-600 dark:bg-amber-900 dark:text-amber-300">!</span>
                    <div>
                        <div class="text-sm font-semibold">{{ __('certificate.verify.status_revoked') }}</div>
                        <div class="mt-0.5 text-xs text-amber-700 dark:text-amber-400">
                            {{ __('certificate.verify.revoked_warning') }}
                        </div>
                    </div>
                </div>
            @else
                <div class="flex items-center gap-3 rounded-lg border border-rose-200 bg-rose-50 p-4 text-rose-800 dark:border-rose-800/60 dark:bg-rose-950/40 dark:text-rose-200">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-rose-100 font-bold text-rose-600 dark:bg-rose-900 dark:text-rose-300">✕</span>
                    <div>
                        <div class="text-sm font-semibold">{{ __('certificate.verify.status_not_found') }}</div>
                        <div class="mt-0.5 text-xs text-rose-700 dark:text-rose-400">
                            {{ __('certificate.verify.not_found_message') }}
                        </div>
                    </div>
                </div>
            @endif

            @if ($verification->status !== 'not_found')
                <dl class="divide-y divide-slate-100 text-sm dark:divide-slate-700/60">
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500 dark:text-slate-400">{{ __('certificate.verify.holder') }}</dt>
                        <dd class="text-right font-medium">{{ $verification->studentName }}</dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500 dark:text-slate-400">{{ __('certificate.verify.school') }}</dt>
                        <dd class="text-right font-medium">{{ $verification->schoolName }}</dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500 dark:text-slate-400">{{ __('certificate.verify.number') }}</dt>
                        <dd class="text-right font-mono text-xs font-semibold">
                            {{ $verification->certificateNumber }}
                        </dd>
                    </div>
                    <div class="flex justify-between py-2.5">
                        <dt class="text-slate-500 dark:text-slate-400">{{ __('certificate.verify.issued_at') }}</dt>
                        <dd class="text-right font-medium">{{ $verification->issuedAt }}</dd>
                    </div>
                    @if ($verification->revokedAt)
                        <div class="flex justify-between py-2.5 text-amber-700 dark:text-amber-400">
                            <dt>{{ __('certificate.verify.revoked_at') }}</dt>
                            <dd class="text-right font-medium">{{ $verification->revokedAt }}</dd>
                        </div>
                    @endif
                </dl>
            @endif
        </div>

        <div class="border-t border-slate-100 bg-slate-50 px-6 py-4 text-center dark:border-slate-700/60 dark:bg-slate-800/50">
            <span class="text-xs text-slate-400 dark:text-slate-500">
                {{ config('app.name') }} &bull; {{ date('Y') }}
            </span>
        </div>
    </div>
</body>
</html>
