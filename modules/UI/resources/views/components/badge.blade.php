@props([
    'value' => null,
    'variant' => 'primary',
])

@php
    $variantClasses = match ($variant) {
        'primary' => 'badge-primary text-primary-content font-semibold whitespace-nowrap',
        'secondary' => 'badge-outline border-base-content/20 text-base-content/70 whitespace-nowrap',
        'warning' => 'badge-warning text-warning-content font-semibold whitespace-nowrap',
        'error' => 'bg-error/10 text-rose-700 border-rose-200/50 border font-bold text-[10px] uppercase tracking-widest whitespace-nowrap',
        'success' => 'bg-success/10 text-emerald-700 border-emerald-200/50 border font-bold text-[10px] uppercase tracking-widest whitespace-nowrap',
        'info' => 'badge-info text-info-content font-semibold whitespace-nowrap',
        'metadata' => 'badge-ghost badge-sm text-base-content/50 font-normal lowercase whitespace-nowrap',
        'custom' => 'whitespace-nowrap',
        default => 'badge-primary text-primary-content whitespace-nowrap',
    };
@endphp

<x-mary-badge {{ $attributes->class(['text-nowrap ' . $variantClasses]) }}>
    <x-slot:value>{{ $value ?? $slot }}</x-slot:value>
</x-mary-badge>
