@php
    $authorName = setting('app_author', 'Reas Vyn');
    $authorGithub = setting('app_github', 'https://github.com/reasnov');
    $brandName = setting('brand_name', 'Internara');
    // Using setting() for app_version as requested, falling back to config if not set
    $version = setting('app_version', config('app.version', 'v0.13.0'));
    
    // Ensure version has 'v' prefix if it's a semantic version string
    if (is_string($version) && !str_starts_with($version, 'v') && preg_match('/^\d/', $version)) {
        $version = 'v' . $version;
    }
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col md:flex-row items-center justify-center gap-2 text-[10px] uppercase tracking-widest font-bold opacity-30']) }}>
    <span>&copy; {{ date('Y') }} {{ $brandName }}</span>
    <span class="hidden md:inline">&bull;</span>
    <span>{{ $version }}</span>
    <span class="hidden md:inline">&bull;</span>
    <span class="normal-case tracking-normal">
        {{ __('ui::common.built_with') }} 
        <span role="img" aria-label="{{ __('ui::common.love') }}">❤️</span> 
        {{ __('ui::common.by') }}
        <a 
            class="hover:text-primary underline transition-colors" 
            href="{{ $authorGithub }}" 
            target="_blank"
            rel="noopener noreferrer"
        >
            {{ $authorName }}
        </a>
    </span>
</div>
