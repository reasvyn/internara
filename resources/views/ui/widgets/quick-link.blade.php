@props([
    'label',
    'icon',
    'link' => '#',
    'color' => null,
])

<a
    href="{{ $link }}"
    wire:navigate
    {{ $attributes->merge(['class' => 'flex items-center gap-3 p-3 rounded-lg hover:bg-base-200/50 transition-colors']) }}
>
    @php $tsIcon = str_starts_with($icon, 'o-') ? substr($icon, 2) : (str_starts_with($icon, 's-') ? substr($icon, 2) : $icon); @endphp
    <x-ts-icon :name="$tsIcon" class="text-base-content/40 size-4 shrink-0" />
    <span class="text-sm">{{ $label }}</span>
    <x-ts-icon name="chevron-right" class="text-base-content/20 ml-auto size-3 shrink-0" />
</a>
