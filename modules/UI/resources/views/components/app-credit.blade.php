@php
    $authorName = setting('app_author', 'Reas Vyn');
    $authorGithub = setting('app_github', 'https://github.com/reasnov');
@endphp

<span {{ $attributes->merge(['class' => 'opacity-70 text-sm']) }}>
    {{ __('ui::common.built_with') }} 
    <span role="img" aria-label="{{ __('ui::common.love') }}">❤️</span> 
    {{ __('ui::common.by') }}
    <a 
        class="hover:text-primary font-bold underline transition-colors" 
        href="{{ $authorGithub }}" 
        target="_blank"
        rel="noopener noreferrer"
        aria-label="{{ __('ui::common.visit_author_github', ['name' => $authorName]) }}"
    >
        {{ $authorName }}
    </a>
</span>
