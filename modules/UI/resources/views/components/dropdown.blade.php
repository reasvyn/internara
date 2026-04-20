@props([
    'label' => null,
    'icon' => null,
    'right' => false,
    'variant' => 'secondary', // primary, secondary, tertiary
    'disabled' => false,
])

@php
    $variantClasses = match ($variant) {
        'primary' => 'btn-accent text-accent-content',
        'secondary' => 'btn-outline border-base-content/20 text-base-content/80',
        'tertiary' => 'btn-ghost text-base-content/70',
        default => 'btn-outline text-base-content/80',
    };
    
    $ariaLabel = $attributes->get('aria-label') ?? $label ?? __('ui::common.options');
    $isDisabled = filter_var($disabled, FILTER_VALIDATE_BOOLEAN);
    $dynamicDisabled = $attributes->get('x-bind:disabled') ?? $attributes->get(':disabled');
    $dynamicClass = $attributes->get('x-bind:class') ?? $attributes->get(':class');
    $dropdownAttributes = $attributes->except([
        'x-bind:disabled',
        ':disabled',
        'x-bind:class',
        ':class',
    ]);
    $triggerAttributes = new \Illuminate\View\ComponentAttributeBag(array_filter([
        'x-bind:disabled' => $dynamicDisabled,
        'x-bind:class' => $dynamicClass,
    ], fn ($value) => filled($value)));
@endphp

<x-mary-dropdown 
    {{ $dropdownAttributes->class([$variantClasses, 'min-h-[2.75rem] relative z-50', 'pointer-events-none opacity-50' => $isDisabled]) }}
    :right="$right"
>
    @isset($trigger)
        <x-slot:trigger>
            {{ $trigger }}
        </x-slot:trigger>
    @else
        <x-slot:trigger>
            <x-ui::button 
                {{ $triggerAttributes }}
                :variant="$variant" 
                :icon="$icon" 
                :label="$label" 
                :spinner="false"
                :class="$isDisabled ? 'btn-disabled' : ''"
                aria-label="{{ $ariaLabel }}" 
            />
        </x-slot:trigger>
    @endisset

    <div class="menu bg-base-100 p-2 shadow-xl border border-base-200 rounded-xl min-w-[12rem]" role="menu">
        {{ $slot }}
    </div>
</x-mary-dropdown>
