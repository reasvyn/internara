@props([
    'icon' => 'inbox',
    'title' => '',
    'description' => '',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center py-12 text-base-content/60']) }}>
    @php $tsIcon = str_starts_with($icon, 'o-') ? substr($icon, 2) : (str_starts_with($icon, 's-') ? substr($icon, 2) : $icon); @endphp
    <x-ts-icon :name="$tsIcon" class="mb-3 size-12" />
    @if ($title)
        <span class="text-sm font-medium">{{ $title }}</span>
    @endif
    @if ($description)
        <span class="mt-1 text-xs">{{ $description }}</span>
    @endif
</div>
