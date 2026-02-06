@props([
    'label' => null,
    'icon' => null,
    'right' => false,
    'priority' => 'secondary', // primary, secondary, tertiary
])

@php
    $priorityClasses = match ($priority) {
        'primary' => 'btn-accent text-white',
        'secondary' => 'btn-outline border-base-content/20 text-base-content/80',
        'tertiary' => 'btn-ghost text-base-content/70',
        default => 'btn-outline',
    };
@endphp

<x-mary-dropdown 
    {{ $attributes->class([$priorityClasses, 'min-h-[2.75rem]']) }}
    :right="$right"
>
    @isset($trigger)
        <x-slot:trigger>
            {{ $trigger }}
        </x-slot:trigger>
    @else
        <x-slot:trigger>
            @if($icon && !$label)
                <x-ui::button :priority="$priority" :icon="$icon" aria-label="{{ $attributes->get('aria-label', __('ui::common.options')) }}" />
            @else
                <x-ui::button :priority="$priority" :icon="$icon" :label="$label" />
            @endif
        </x-slot:trigger>
    @endisset

    <div class="menu bg-base-100 p-2 shadow-xl border border-base-200 rounded-xl min-w-[12rem]" role="menu">
        {{ $slot }}
    </div>
</x-mary-dropdown>
