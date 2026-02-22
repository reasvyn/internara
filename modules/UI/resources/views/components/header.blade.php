@props([
    'title' => null,
    'subtitle' => null,
    'context' => null,
    'middle' => null, 
    'actions' => null,
    'separator' => false,
])

<div {{ $attributes->merge(['class' => 'mb-10']) }}>
    {{-- Breadcrumb Context --}}
    @if($context)
        <div class="mb-2 flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.15em] text-base-content/40">
            <span>{{ setting('brand_name', 'Internara') }}</span>
            <x-ui::icon name="tabler.chevron-right" class="size-2.5" />
            <span>{{ __($context) }}</span>
        </div>
    @endif

    {{-- Main Header Content --}}
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="flex-1 space-y-1">
            @if($title)
                <h2 class="text-3xl font-black tracking-tight text-base-content lg:text-4xl">
                    {{ $title }}
                </h2>
            @endif

            @if($subtitle)
                <p class="text-sm leading-relaxed text-base-content/60 max-w-2xl">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        @if($middle)
            <div class="flex items-center">
                {{ $middle }}
            </div>
        @endif

        @if($actions)
            <div class="flex flex-none items-center gap-3">
                {{ $actions }}
            </div>
        @endif
    </div>

    @if($separator)
        <div class="divider mt-8 opacity-10"></div>
    @endif
</div>
