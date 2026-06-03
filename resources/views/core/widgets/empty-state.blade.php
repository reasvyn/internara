@props([
    'icon' => 'o-inbox',
    'title' => '',
    'description' => '',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center py-12 text-base-content/20']) }}>
    <x-mary-icon :name="$icon" class="size-12 mb-3" />
    @if($title)
        <span class="text-sm font-medium">{{ $title }}</span>
    @endif
    @if($description)
        <span class="text-xs mt-1">{{ $description }}</span>
    @endif
</div>
