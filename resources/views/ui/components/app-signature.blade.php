@php
    $appName = app_info('name');
    $appVersion = app_info('version');
    $appLicense = app_info('license', 'MIT');
    $author = app_info('author');
@endphp

<div class="border-base-content/10 mt-4 border-t py-3 text-center sm:mt-6 sm:py-4">
    <div class="flex flex-col items-center justify-center gap-2 sm:flex-row sm:gap-4">
        <div class="flex items-center gap-2">
            <span class="text-base-content/60 text-xs font-bold tracking-widest uppercase">&copy; {{ date('Y') }}</span>
            <span class="text-base-content/60 text-xs font-bold tracking-tight">{{ $appName }}</span>
        </div>

        <div class="bg-base-content/10 hidden size-1 rounded-full sm:block"></div>

        <x-ts-badge :text="$appVersion" color="gray" xs class="opacity-50" />

        @if (! empty($author))
            <div class="bg-base-content/10 hidden size-1 rounded-full sm:block"></div>
            <div class="flex items-center gap-1.5">
                <span class="text-base-content/60 text-xs font-bold tracking-widest uppercase">{{ __('common.handcrafted_by') }}</span>
                @if (! empty($author['github']))
                    <a
                        href="{{ $author['github'] }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-primary text-xs font-bold tracking-tight decoration-2 underline-offset-4 transition-all hover:underline"
                    >{{ $author['name'] ?? $author }}</a>
                @else
                    <span class="text-base-content/60 text-xs font-bold tracking-tight">{{ $author['name'] ?? $author }}</span>
                @endif
            </div>
        @endif

        <div class="bg-base-content/10 hidden size-1 rounded-full sm:block"></div>

        <span class="text-base-content/60 text-xs font-bold tracking-widest uppercase">{{ $appLicense }} License</span>
    </div>
</div>
