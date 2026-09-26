@props([
    'variant' => 'dropdown', // dropdown | inline
    'size' => 'sm',
])

@php
    $current = app()->getLocale();
    $locales = [
        'en' => ['label' => __('common.language.english'), 'short' => 'EN', 'flag' => '🇬🇧'],
        'id' => ['label' => __('common.language.indonesian'), 'short' => 'ID', 'flag' => '🇮🇩'],
    ];
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
                <span class="mr-1" aria-hidden="true">{{ $meta['flag'] }}</span>{{ $meta['short'] }}
            </button>
        @endforeach
    </div>
@else
    <x-ts-dropdown position="bottom-end">
        <x-slot:action>
            <button
                type="button"
                x-on:click="show = ! show"
                class="btn btn-ghost {{ $sizeClasses }} rounded-full font-bold tracking-wider uppercase"
                aria-label="{{ __('common.language.switch') }}"
            >
                <span
                    class="text-sm leading-none"
                    aria-hidden="true"
                >{{ $locales[$current]['flag'] ?? ($current === 'id' ? '🇮🇩' : '🇬🇧') }}</span>
                <span aria-hidden="true">{{ strtoupper($current) }}</span>
            </button>
        </x-slot:action>

        <div class="min-w-48">
            @foreach ($locales as $code => $meta)
                @if (isset($__livewire))
                    <x-ts-dropdown.items wire:click="changeLocale('{{ $code }}')" :active="$current === $code">
                        <span class="inline-flex items-center gap-2">
                            <span class="text-base leading-none" aria-hidden="true">{{ $meta['flag'] }}</span>
                            <span>{{ $meta['label'] }}</span>
                        </span>
                    </x-ts-dropdown.items>
                @else
                    <x-ts-dropdown.items :href="route('locale.switch', $code)" :active="$current === $code">
                        <span class="inline-flex items-center gap-2">
                            <span class="text-base leading-none" aria-hidden="true">{{ $meta['flag'] }}</span>
                            <span>{{ $meta['label'] }}</span>
                        </span>
                    </x-ts-dropdown.items>
                @endif
            @endforeach
        </div>
    </x-ts-dropdown>
@endif
