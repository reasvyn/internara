@props([
    'label' => null,
    'labelClass' => null,
    'icon' => null,
    'right' => false,
    'variant' => 'secondary', // primary, secondary, tertiary
    'disabled' => false,
    'closeOnContentClick' => true,
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
    $shouldCloseOnContentClick = filter_var($closeOnContentClick, FILTER_VALIDATE_BOOLEAN);
@endphp

<details
    x-data="{ open: false }"
    @click.outside="open = false"
    :open="open"
    class="dropdown overflow-visible"
>
    @isset($trigger)
        <summary
            x-ref="button"
            @click.prevent="if (!{{ $isDisabled ? 'true' : 'false' }}) open = !open"
            {{ $trigger->attributes->class(['list-none', 'pointer-events-none opacity-50' => $isDisabled]) }}
        >
            {{ $trigger }}
        </summary>
    @else
        <summary
            x-ref="button"
            @click.prevent="if (!{{ $isDisabled ? 'true' : 'false' }}) open = !open"
            {{ $attributes->class([$variantClasses, 'btn min-h-[2.75rem] relative z-[1000] list-none', 'pointer-events-none opacity-50' => $isDisabled]) }}
            aria-label="{{ $ariaLabel }}"
        >
            @if($label)
                <span class="{{ $labelClass }}">{{ $label }}</span>
            @endif
            @if($icon)
                <x-mary-icon :name="$icon" />
            @endif
        </summary>
    @endisset

    <ul
        x-anchor.{{ $right ? 'bottom-end' : 'bottom-start' }}="$refs.button"
        @if($shouldCloseOnContentClick) @click="open = false" @endif
        class="menu z-[1000] min-w-[12rem] rounded-xl border border-base-200 bg-base-100 p-2 shadow-xl"
        role="menu"
    >
        <div>
            {{ $slot }}
        </div>
    </ul>
</details>
