@props([
    'fullWidth' => false,
    'showCredit' => true,
    'links' => null, // array of ['label' => '', 'url' => '']
])

{{-- Modern responsive Footer — props-driven, minimal. --}}
<footer
    {{ $attributes->merge([
        'class' => 'mt-auto border-t bg-base-100 border-base-content/10',
    ]) }}
    role="contentinfo"
>
    <div @class([
        'mx-auto px-4 sm:px-6 lg:px-8 py-6',
        'container max-w-7xl' => ! $fullWidth,
        'w-full' => $fullWidth,
    ])>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            {{-- Left: brand + links --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-6">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2 text-sm font-medium hover:text-primary transition-colors">
                    <x-ui::components.logo size="4" />
                    <span>{{ brand('name') }}</span>
                </a>

                @if ($links)
                    <nav aria-label="{{ __('common.footer_navigation') }}" class="flex flex-wrap items-center gap-4 text-xs text-base-content/60">
                        @foreach ($links as $link)
                            <a href="{{ $link['url'] }}" class="hover:text-primary transition-colors hover:underline underline-offset-4">
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </nav>
                @endif

                {{ $slot }}
            </div>

            {{-- Right: credit --}}
            @if ($showCredit)
                <div class="flex items-center gap-3 text-xs text-base-content/50">
                    <x-ui::components.credit :show-version="app()->environment('local')" class="justify-center sm:justify-end" />
                </div>
            @endif
        </div>

        <div class="mt-4 border-t border-base-content/5 pt-4 text-center text-xs text-base-content/40 sm:text-left">
            <p>&copy; {{ date('Y') }} {{ brand('name') }} — {{ __('common.all_rights_reserved') }}</p>
        </div>
    </div>
</footer>
