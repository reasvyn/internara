@props([
    'variant' => 'dropdown', // dropdown | inline
    'size' => 'sm',
])

@php
    $current = app()->getLocale();
    $locales = [];
    foreach (\App\Modules\Setting\Domain\Locale\Support\Locale::all() as $code => $meta) {
        $locales[$code] = [
            'label' => \App\Modules\Setting\Domain\Locale\Support\Locale::label((string) $code),
            'short' => strtoupper((string) $code),
            'flag' => \App\Modules\Setting\Domain\Locale\Support\Locale::flag((string) $code),
            'country_code' => \App\Modules\Setting\Domain\Locale\Support\Locale::countryCode((string) $code),
        ];
    }
    $sizeClasses = match ($size) {
        'xs' => 'btn-xs text-xs',
        'sm' => 'btn-sm text-xs',
        'md' => 'btn-md text-sm',
        default => 'btn-sm text-xs',
    };
@endphp

@if ($variant === 'inline')
    <div
        {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full bg-base-200 p-1']) }}
        role="group"
        aria-label="{{ __('common.language.switch') }}"
    >
        @foreach ($locales as $code => $meta)
            <button
                type="button"
                @if (isset($__livewire))
                    wire:click="changeLocale('{{ $code }}')"
                @else
                    x-on:click="document.cookie='locale={{ $code }};path=/'; location.reload()"
                @endif
                class="px-3 py-1 text-xs font-bold rounded-full transition {{ $current === $code ? 'bg-primary text-primary-content shadow-sm' : 'text-base-content/60 hover:text-base-content' }}"
                :aria-current="$current === $code ? 'true' : 'false'"
            >
                <span class="fi fi-{{ $meta['country_code'] }} mr-1 rounded-xs shadow-xs" aria-hidden="true"></span
                >{{ $meta['short'] }}
            </button>
        @endforeach
    </div>
@else
    <x-ts-dropdown position="bottom-end">
        <x-slot:action>
            <button
                type="button"
                x-on:click="show = ! show"
                class="hover:bg-base-200 focus-visible:outline-primary inline-flex items-center gap-1.5 {{ $sizeClasses }} rounded-full font-bold tracking-wider uppercase transition-colors focus-visible:outline-2"
                aria-label="{{ __('common.language.switch') }}"
            >
                <span
                    class="fi fi-{{ $locales[$current]['country_code'] ?? 'gb' }} text-sm leading-none rounded-xs shadow-xs"
                    aria-hidden="true"
                ></span>
                <span aria-hidden="true">{{ strtoupper($current) }}</span>
            </button>
        </x-slot:action>

        <div class="min-w-48">
            @foreach ($locales as $code => $meta)
                @if (isset($__livewire))
                    <x-ts-dropdown.items wire:click="changeLocale('{{ $code }}')" :active="$current === $code">
                        <span class="inline-flex items-center gap-2">
                            <span
                                class="fi fi-{{ $meta['country_code'] }} text-base leading-none rounded-xs shadow-xs"
                                aria-hidden="true"
                            ></span>
                            <span>{{ $meta['label'] }}</span>
                        </span>
                    </x-ts-dropdown.items>
                @else
                    <x-ts-dropdown.items
                        x-on:click="document.cookie='locale={{ $code }};path=/'; location.reload()"
                        :active="$current === $code"
                    >
                        <span class="inline-flex items-center gap-2">
                            <span
                                class="fi fi-{{ $meta['country_code'] }} text-base leading-none rounded-xs shadow-xs"
                                aria-hidden="true"
                            ></span>
                            <span>{{ $meta['label'] }}</span>
                        </span>
                    </x-ts-dropdown.items>
                @endif
            @endforeach
        </div>
    </x-ts-dropdown>
@endif
