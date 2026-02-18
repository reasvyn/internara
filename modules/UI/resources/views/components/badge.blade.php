@props([
    'value' => null,
    'variant' => 'primary',
])

@php
    $variantClasses = match ($variant) {
        'primary' => 'badge-primary text-primary-content font-semibold whitespace-nowrap',
        'secondary' => 'badge-outline border-base-content/20 text-base-content/70 whitespace-nowrap',
        'warning' => 'badge-warning text-warning-content font-semibold whitespace-nowrap',
        'error' => 'badge-error text-error-content font-semibold whitespace-nowrap',
        'success' => 'badge-success text-success-content font-semibold whitespace-nowrap',
        'info' => 'badge-info text-info-content font-semibold whitespace-nowrap',
        'metadata' => 'badge-ghost badge-sm text-base-content/50 font-normal lowercase whitespace-nowrap',
        'custom' => 'whitespace-nowrap',
        default => 'badge-primary text-primary-content whitespace-nowrap',
    };
@endphp

<x-mary-badge {{ $attributes->class([$variantClasses]) }}>
    <x-slot:value>{{ $value ?? $slot }}</x-slot:value>
</x-mary-badge>
