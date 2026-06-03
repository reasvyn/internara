@props([
    'label',
    'icon',
    'link' => '#',
    'color' => null,
])

<a href="{{ $link }}" wire:navigate {{ $attributes->merge(['class' => 'flex items-center gap-3 p-3 rounded-lg hover:bg-base-200/50 transition-colors']) }}>
    <x-mary-icon :name="$icon" class="size-4 shrink-0 text-base-content/40" />
    <span class="text-sm">{{ $label }}</span>
    <x-mary-icon name="o-chevron-right" class="size-3 text-base-content/20 ml-auto shrink-0" />
</a>
