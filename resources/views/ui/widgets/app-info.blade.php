@props([
    'compact' => false,
])

@php
    $appName = brand('name') ?: config('app.name', 'Internara');
    $appVersion = app_info('version') ?: config('app.version', '—');
    $env = app()->environment();
    $phpVersion = PHP_VERSION;
    $laravelVersion = app()->version();
@endphp

<x-ts-card shadowless class="bg-base-100 border-base-content/10 border">
    <x-slot:header>
        <div class="flex items-center gap-2">
            <div class="bg-primary/10 text-primary flex size-6 items-center justify-center rounded-md">
                <x-ts-icon name="information-circle" class="size-3.5" />
            </div>
            <span class="text-sm font-semibold">{{ __('dashboard.app_info.title') }}</span>
        </div>
    </x-slot:header>

    <div @class(['space-y-3', 'mt-2' => ! $compact])>
        <div class="flex items-center justify-between text-xs">
            <span class="text-neutral-800 dark:text-white/70">{{ __('dashboard.app_info.name') }}</span>
            <span class="font-semibold text-neutral-800 dark:text-white">{{ $appName }}</span>
        </div>
        <div class="flex items-center justify-between text-xs">
            <span class="text-neutral-800 dark:text-white/70">{{ __('dashboard.app_info.version') }}</span>
            <x-ts-badge :text="$appVersion" class="badge-neutral badge-sm" />
        </div>
        <div class="flex items-center justify-between text-xs">
            <span class="text-neutral-800 dark:text-white/70">{{ __('dashboard.app_info.environment') }}</span>
            <span class="font-semibold uppercase text-neutral-800 dark:text-white">{{ $env }}</span>
        </div>
        @if (! $compact)
            <div class="border-base-content/10 mt-3 space-y-2 border-t pt-3 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-neutral-800 dark:text-white/70">{{ __('dashboard.app_info.php_version') }}</span>
                    <span class="font-medium text-neutral-800 dark:text-white">{{ $phpVersion }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-neutral-800 dark:text-white/70">{{ __('dashboard.app_info.laravel_version') }}</span>
                    <span class="font-medium text-neutral-800 dark:text-white">{{ $laravelVersion }}</span>
                </div>
            </div>
            <p class="text-base-content/40 pt-2 text-[10px] leading-relaxed">
                {{ __('dashboard.app_info.tagline') }}
            </p>
        @endif
    </div>
</x-ts-card>
