@props([
    'label' => null,
    'icon' => null,
    'right' => false,
    'variant' => 'secondary', // primary, secondary, tertiary
])

@php
    $variantClasses = match ($variant) {
        'primary' => 'btn-accent text-accent-content',
        'secondary' => 'btn-outline border-base-content/20 text-base-content/80',
        'tertiary' => 'btn-ghost text-base-content/70',
        default => 'btn-outline text-base-content/80',
    };
    
    $ariaLabel = $attributes->get('aria-label') ?? $label ?? __('ui::common.options');
@endphp

<x-mary-dropdown 
    {{ $attributes->class([$variantClasses, 'min-h-[2.75rem] relative z-50']) }}
    :right="$right"
>
    @isset($trigger)
        <x-slot:trigger>
            {{ $trigger }}
        </x-slot:trigger>
    @else
        <x-slot:trigger>
            <x-ui::button 
                :variant="$variant" 
                :icon="$icon" 
                :label="$label" 
                :spinner="false"
                aria-label="{{ $ariaLabel }}" 
            />
        </x-slot:trigger>
    @endisset

    <div class="menu bg-base-100 p-2 shadow-xl border border-base-200 rounded-xl min-w-[12rem]" role="menu">
        {{ $slot }}
    </div>
</x-mary-dropdown>
