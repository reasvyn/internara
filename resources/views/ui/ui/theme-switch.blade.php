@props([
    'size' => 'sm',
    'showLabel' => false,
    'label' => null,
])

@php
    $labelText = $label ?? __('common.theme.switch');
    $sizeClasses = match ($size) {
        'xs' => 'btn-xs',
        'sm' => 'btn-sm',
        'md' => 'btn-md',
        default => 'btn-sm',
    };
@endphp

<div
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}
    x-data="tallstackui_darkTheme()"
    x-init="init()"
>
    <button
        type="button"
        @click="toggle()"
        class="btn btn-ghost {{ $sizeClasses }} gap-2 rounded-full"
        :aria-label="'{{ $labelText }} (' + (dark ? '{{ __('common.theme.dark') }}' : '{{ __('common.theme.light') }}') + ')'"
        :title="dark ? '{{ __('common.theme.switch_to_light') }}' : '{{ __('common.theme.switch_to_dark') }}'"
    >
        {{-- Sun (light) --}}
        <x-ts-icon name="sun" class="size-4" x-show="!dark" x-cloak />
        {{-- Moon (dark) --}}
        <x-ts-icon name="moon" class="size-4" x-show="dark" x-cloak />
        @if ($showLabel)
            <span class="hidden text-xs font-medium sm:inline" x-text="dark ? '{{ __('common.theme.dark') }}' : '{{ __('common.theme.light') }}'"></span>
        @endif
    </button>
</div>

