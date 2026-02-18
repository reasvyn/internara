@props([
    'value' => null,
    'variant' => 'primary',
])

@php
    $variantClasses = match ($variant) {
        'primary' => 'badge-primary text-primary-content font-semibold',
        'secondary' => 'badge-outline border-base-content/20 text-base-content/70',
        'metadata' => 'badge-ghost badge-sm text-base-content/50 font-normal lowercase',
        'custom' => '',
        default => 'badge-primary text-primary-content',
    };
@endphp

<x-mary-badge {{ $attributes->class([$variantClasses]) }}>
    <x-slot:value>{{ $value ?? $slot }}</x-slot:value>
</x-mary-badge>
