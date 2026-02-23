@props([
    'title' => null,
    'subtitle' => null,
    'middle' => null, 
    'actions' => null,
    'separator' => false,
])

<div {{ $attributes->merge(['class' => 'mb-10']) }}>
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
