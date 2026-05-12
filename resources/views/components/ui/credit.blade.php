@props(['showVersion' => false])

@php
    $author = App\Support\AppInfo::author();
    $authorName = $author['name'] ?? '';
    $authorUrl = $author['github'] ?? '';
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row items-center gap-2 text-xs text-base-content/40']) }}>
    <span>&copy; {{ date('Y') }} {{ brand('name') }}. {{ __('common.all_rights_reserved') }}</span>

    @if($showVersion)
        <span class="w-px h-4 bg-base-content/10 hidden sm:block"></span>
        <span class="font-mono">v{{ App\Support\AppInfo::version() }}</span>
    @endif

    <span class="w-px h-4 bg-base-content/10 hidden sm:block"></span>

    <span>
        {{ __('common.built_with_love') }}
        @if($authorUrl)
            <a href="{{ $authorUrl }}" target="_blank" rel="noopener noreferrer" class="text-primary hover:underline font-medium">
                {{ $authorName }}
            </a>
        @else
            <span class="font-medium">{{ $authorName }}</span>
        @endif
    </span>
</div>
