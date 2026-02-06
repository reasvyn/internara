<span {{ $attributes->merge(['class' => 'opacity-70']) }}>
    {{ __('ui::common.built_with') }} ❤️ {{ __('ui::common.by') }}
    <a class="hover:text-primary font-bold underline transition-colors" href="{{ setting('app_github', 'https://github.com/reasnov') }}" target="_blank">
        {{ setting('app_author') }}
    </a>
</span>
