@props([
    'user',
    'size' => 'size-9',
    'textSize' => null,
])

@php
    $avatarUrl = $user?->getAvatarUrl();
    $initials = filled($user?->initials()) ? $user->initials() : '?';
    $fontSize = $textSize ?? match ($size) {
        'size-24', 'size-20' => 'text-2xl font-bold',
        'size-16' => 'text-lg font-bold',
        'size-14', 'size-12' => 'text-base font-semibold',
        'size-10' => 'text-sm font-semibold',
        default => 'text-xs font-medium',
    };
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
        <span x-show="imgFailed" x-cloak class="text-base-content/60 {{ $fontSize }}"> {{ $initials }} </span>
    @else
        <span class="text-base-content/60 {{ $fontSize }}">{{ $initials }}</span>
    @endif
</div>
