@props([
    'variant' => 'dropdown', // dropdown | inline
    'size' => 'sm',
])

@php
    $current = app()->getLocale();
    $locales = [
        'en' => ['label' => __('common.language.english'), 'short' => 'EN'],
        'id' => ['label' => __('common.language.indonesian'), 'short' => 'ID'],
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
                    wire:click="setLocale('{{ $code }}')"
                @else
                    onclick="document.cookie='locale={{ $code }};path=/'; location.reload()"
                @endif
                class="px-3 py-1 text-xs font-bold rounded-full transition {{ $current === $code ? 'bg-primary text-primary-content shadow-sm' : 'text-base-content/60 hover:text-base-content' }}"
                :aria-current="$current === $code ? 'true' : 'false'"
            >
                {{ $meta['short'] }}
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
                <x-ts-icon name="globe-alt" class="size-4 opacity-60" />
                <span aria-hidden="true">{{ strtoupper($current) }}</span>
            </button>
        </x-slot:action>

        <div class="min-w-48">
            @foreach ($locales as $code => $meta)
                @if (isset($__livewire))
                    <x-ts-dropdown.items
                        :text="$meta['label']"
                        icon="globe-alt"
                        wire:click="setLocale('{{ $code }}')"
                        :active="$current === $code"
                    />
                @else
                    <x-ts-dropdown.items
                        :text="$meta['label']"
                        icon="globe-alt"
                        :href="route('locale.switch', $code)"
                        :active="$current === $code"
                    />
                @endif
            @endforeach
        </div>
    </x-ts-dropdown>
@endif
