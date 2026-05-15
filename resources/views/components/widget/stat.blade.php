@props([
    'icon',
    'label',
    'value',
    'color' => 'primary',
])

@php
    $iconColors = [
        'primary' => 'bg-primary/10 text-primary',
        'success' => 'bg-success/10 text-success',
        'warning' => 'bg-warning/10 text-warning',
        'error' => 'bg-error/10 text-error',
        'info' => 'bg-info/10 text-info',
        'secondary' => 'bg-secondary/10 text-secondary',
        'accent' => 'bg-accent/10 text-accent',
    ];
    $iconClass = $iconColors[$color] ?? $iconColors['primary'];
@endphp

<x-mary-card class="bg-base-100 border border-base-content/10">
    <div class="flex items-center gap-3">
        <div class="size-10 rounded-lg flex items-center justify-center shrink-0 {{ $iconClass }}">
            <x-mary-icon :name="$icon" class="size-5" />
        </div>
        <div>
            <p class="text-xs text-base-content/50 font-medium uppercase tracking-wider">{{ $label }}</p>
            <p class="text-xl font-bold">{{ $value }}</p>
        </div>
    </div>
</x-mary-card>
