@props([
    'user',
    'size' => 'size-9',
])

@php
    $avatarUrl = $user->getFirstMediaUrl('avatar', 'thumb');
@endphp

<div {{ $attributes->merge(['class' => $size . ' rounded-lg bg-base-200 flex items-center justify-center shrink-0 overflow-hidden']) }}>
    @if($avatarUrl)
        <img src="{{ $avatarUrl }}"
             alt="{{ $user->name }}"
             class="size-full object-cover" />
    @else
        <span class="text-xs font-medium text-base-content/60">{{ $user->initials() }}</span>
    @endif
</div>
