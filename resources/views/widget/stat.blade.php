@props([
    'label',
    'value',
    'icon',
    'color' => 'text-primary',
])

<div {{ $attributes->merge(['class' => 'bg-base-100 border border-base-content/10 rounded-xl p-5']) }}>
    <div class="flex items-center gap-4">
        <div @class(["shrink-0 size-10 rounded-lg bg-base-200/50 flex items-center justify-center {$color}"])>
            <x-mary-icon :name="$icon" class="size-5" />
        </div>
        <div class="min-w-0">
            <p class="text-xs text-base-content/50 truncate">{{ $label }}</p>
            <p class="text-2xl font-bold tracking-tight">{{ $value }}</p>
        </div>
    </div>
</div>
