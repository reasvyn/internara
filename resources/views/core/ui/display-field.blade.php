@props([
    'label' => '',
    'value' => '',
    'icon' => null,
])

<div>
    @if ($label)
        <label class="text-base-content/50 mb-1 block text-xs">{{ $label }}</label>
    @endif
    <div class="bg-base-200/30 border-base-content/10 flex items-center gap-2 rounded-lg border px-3 py-2">
        @if ($icon)
            @php $tsIcon = str_starts_with($icon, 'o-') ? substr($icon, 2) : (str_starts_with($icon, 's-') ? substr($icon, 2) : $icon); @endphp
            <x-ts-icon :name="$tsIcon" class="text-base-content/40 size-4 shrink-0" />
        @endif
        <span class="text-base-content text-sm font-medium">{{ $value }}</span>
    </div>
</div>
