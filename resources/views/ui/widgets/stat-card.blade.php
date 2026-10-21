@props([
    'title',
    'value',
    'icon',
    'color' => 'text-primary',
])

<div {{ $attributes->merge(['class' => 'bg-base-100 border border-base-content/10 rounded-2xl p-4 sm:p-5 shadow-2xs transition-all duration-200 hover:shadow-xs']) }}>
    <div class="flex items-center gap-4">
        <div @class(["shrink-0 size-10 rounded-2xl bg-base-200/50 flex items-center justify-center {$color}"])>
            @php $tsIcon = str_starts_with($icon, 'o-') ? substr($icon, 2) : (str_starts_with($icon, 's-') ? substr($icon, 2) : $icon); @endphp
            <x-ts-icon :name="$tsIcon" class="size-5" />
        </div>
        <div class="min-w-0">
            {{-- Wrap instead of truncating: six-across dashboards clip long stat labels. --}}
            <p class="text-base-content/50 line-clamp-2 text-xs leading-snug">{{ $title }}</p>
            <p class="text-xl font-bold tracking-tight sm:text-2xl">{{ $value }}</p>
        </div>
    </div>
</div>
