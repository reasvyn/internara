@props([
    'user',
    'size' => 'size-9',
])

@php
    $avatarUrl = $user?->getAvatarUrl();
@endphp

<div
    {{ $attributes->merge(['class' => $size . ' rounded-full bg-base-200 flex items-center justify-center shrink-0 overflow-hidden']) }}
    x-data="{ imgFailed: false }"
>
    @if ($avatarUrl)
        <img
            src="{{ $avatarUrl }}"
            alt="{{ $user?->name ?? '' }}"
            class="size-full object-cover"
            x-show="! imgFailed"
            x-on:error="imgFailed = true"
        />
        <span x-show="imgFailed" x-cloak class="text-base-content/60 text-xs font-medium">
            {{ $user?->initials() ?? '?' }}
        </span>
    @else
        <span class="text-base-content/60 text-xs font-medium">{{ $user?->initials() ?? '?' }}</span>
    @endif
</div>
