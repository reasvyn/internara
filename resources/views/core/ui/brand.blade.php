@props([
    'size' => 'md',
    'withName' => true,
    'withTagline' => false,
    'invert' => true,
])

@php
    $sizeClasses = match($size) {
        'xs' => 'size-6',
        'sm' => 'size-8',
        'md' => 'size-10',
        'lg' => 'size-14',
        'xl' => 'size-20',
        default => 'size-10',
    };

    $nameClasses = match($size) {
        'xs' => 'text-xs',
        'sm' => 'text-sm',
        'md' => 'text-xl',
        'lg' => 'text-2xl',
        'xl' => 'text-4xl',
        default => 'text-xl',
    };
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-3 group min-w-0']) }}>
    <img
        src="{{ brand('logo') }}"
        @class([
            'object-contain transition-transform group-hover:scale-110 duration-500',
            'brightness-0 invert' => $invert,
            $sizeClasses
        ])
        alt="{{ brand('name') }}"
    />

    @if($withName)
        <div class="flex flex-col min-w-0">
            <span @class([
                'font-black tracking-tighter leading-none transition-colors group-hover:text-primary truncate',
                $nameClasses
            ]) title="{{ brand('name') }}">
                {{ brand('name') }}
            </span>
            @if($withTagline)
                <span class="text-[9px] uppercase tracking-[0.3em] font-black opacity-30 mt-1">
                    {{ brand('tagline') ?? 'Management System' }}
                </span>
            @endif
        </div>
    @endif
</div>
