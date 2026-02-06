@props([
    'type' => 'info', // info, success, warning, error
    'icon' => null,
    'title' => null,
    'description' => null,
])

@php
    $defaultIcon = match ($type) {
        'success' => 'tabler.check',
        'warning' => 'tabler.alert-triangle',
        'error' => 'tabler.alert-circle',
        default => 'tabler.info-circle',
    };

    $alertClasses = match ($type) {
        'success' => 'alert-success bg-success/10 border-success/20 text-success-content',
        'warning' => 'alert-warning bg-warning/10 border-warning/20 text-warning-content',
        'error' => 'alert-error bg-error/10 border-error/20 text-error-content',
        default => 'alert-info bg-info/10 border-info/20 text-info-content',
    };
@endphp

<div {{ $attributes->class(['alert shadow-sm border', $alertClasses]) }} role="alert">
    <x-ui::icon :name="$icon ?? $defaultIcon" class="size-6 shrink-0" />
    
    <div class="flex flex-col gap-1">
        @if($title)
            <h4 class="font-bold leading-tight">{{ $title }}</h4>
        @endif
        
        @if($description || $slot->isNotEmpty())
            <div class="text-sm opacity-90">
                {{ $description ?? $slot }}
            </div>
        @endif
    </div>
</div>
