@props([
    'title' => null,
    'value' => null,
    'icon' => null,
    'description' => null,
    'variant' => 'primary', // primary, secondary, accent, info, success, warning, error
    'aos' => 'fade-up',
])

@php
    $variantClasses = match ($variant) {
        'primary' => 'text-primary',
        'secondary' => 'text-secondary',
        'accent' => 'text-accent',
        'info' => 'text-info',
        'success' => 'text-success',
        'warning' => 'text-warning',
        'error' => 'text-error',
        default => 'text-primary',
    };
@endphp

<div 
    {{ $attributes->merge(['class' => 'stats shadow-sm border border-base-200 bg-base-100 rounded-2xl w-full']) }}
    data-aos="{{ $aos }}"
>
    <div class="stat">
        @if($icon)
            <div class="stat-figure {{ $variantClasses }} opacity-30">
                <x-ui::icon :name="$icon" class="size-8" />
            </div>
        @endif
        
        <div class="stat-title text-xs font-black uppercase tracking-widest opacity-60">{{ $title }}</div>
        <div class="stat-value text-3xl font-black {{ $variantClasses }}">{{ $value }}</div>
        
        @if($description)
            <div class="stat-desc mt-1 opacity-60">{{ $description }}</div>
        @endif
    </div>
</div>
