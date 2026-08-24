@php
    $appName = app_info('name');
    $appVersion = app_info('version');
    $appLicense = app_info('license', 'MIT');
    $author = app_info('author');
@endphp

<div class="border-base-content/5 mt-8 border-t py-6 text-center">
    <div class="flex flex-col items-center justify-center gap-2 sm:flex-row sm:gap-4">
        <div class="flex items-center gap-2">
            <span class="text-base-content/60 text-[10px] font-black tracking-widest uppercase">&copy; {{ date('Y') }}</span>
            <span class="text-base-content/60 text-xs font-black tracking-tight">{{ $appName }}</span>
        </div>

        <div class="bg-base-content/10 hidden size-1 rounded-full sm:block"></div>

        <x-ts-badge :text="$appVersion" color="white" xs class="opacity-50" />

        @if (! empty($author))
            <div class="bg-base-content/10 hidden size-1 rounded-full sm:block"></div>
            <div class="flex items-center gap-1.5">
                <span class="text-base-content/60 text-[10px] font-black tracking-widest uppercase">Handcrafted by</span>
                @if (! empty($author['github']))
                    <a
                        href="{{ $author['github'] }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-primary text-xs font-black tracking-tight decoration-2 underline-offset-4 transition-all hover:underline"
                    >{{ $author['name'] ?? $author }}</a>
                @else
                    <span class="text-base-content/60 text-xs font-black tracking-tight">{{ $author['name'] ?? $author }}</span>
                @endif
            </div>
        @endif

        <div class="bg-base-content/10 hidden size-1 rounded-full sm:block"></div>

        <span class="text-base-content/60 text-[10px] font-black tracking-widest uppercase">{{ $appLicense }} License</span>
    </div>
</div>
