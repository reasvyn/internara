@props([
    'label' => null,
    'icon' => null,
    'hint' => null,
    'displayed' => false,
])

<div class="w-full">
    @if($label)
        <label class="label mb-1 px-1">
            <span class="label-text font-semibold text-base-content/80">{{ $label }}</span>
        </label>
    @endif

    @if($displayed)
        <div 
            {{ $attributes->merge(['class' => 'input input-bordered bg-base-200/50 flex items-center opacity-80 cursor-default w-full']) }}
        >
            @if($icon)
                <x-ui::icon :name="$icon" class="size-3 mr-2 opacity-50" />
            @endif
            <span class="text-sm font-medium">{{ $attributes->get('value') ?? $slot }}</span>
        </div>
        @if($hint)
            <div class="px-1 mt-1">
                <span class="text-xs opacity-40 italic text-wrap w-full">{{ $hint }}</span>
            </div>
        @endif
    @else
        <x-mary-input
            {{ $attributes->merge(['class' => 'input-bordered focus:border-accent focus:ring-accent w-full']) }}
            :icon="$icon"
            :hint="$hint"
            aria-label="{{ $label ?? $attributes->get('placeholder') }}"
        />
    @endif
</div>
