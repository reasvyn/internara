@props([
    'label' => '',
    'value' => '',
    'icon' => null,
])

<div>
    @if($label)
        <label class="text-xs text-base-content/50 block mb-1">{{ $label }}</label>
    @endif
    <div class="flex items-center gap-2 py-2 px-3 bg-base-200/30 rounded-lg border border-base-content/10">
        @if($icon)
            <x-mary-icon :name="$icon" class="size-4 text-base-content/40 shrink-0" />
        @endif
        <span class="text-sm font-medium text-base-content">{{ $value }}</span>
    </div>
</div>
