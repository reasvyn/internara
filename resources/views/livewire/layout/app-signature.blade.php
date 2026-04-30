<div class="text-center text-xs text-base-content/50 py-4">
    <div class="flex flex-col sm:flex-row items-center justify-center gap-1 sm:gap-2">
        <span>&copy; {{ date('Y') }} {{ $app_name }}</span>
        <span class="hidden sm:inline">&middot;</span>
        <span>{{ $app_version }}</span>
        @if (!empty($author))
            <span class="hidden sm:inline">&middot;</span>
            <span>
                @if (!empty($author['github']))
                    <a href="{{ $author['github'] }}" target="_blank" rel="noopener noreferrer" class="link link-hover link-primary">{{ $author['name'] ?? $author }}</a>
                @else
                    {{ $author['name'] ?? $author }}
                @endif
            </span>
        @endif
        <span class="hidden sm:inline">&middot;</span>
        <span>{{ $app_license }}</span>
    </div>
</div>
