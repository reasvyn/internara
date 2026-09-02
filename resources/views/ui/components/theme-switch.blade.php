@props([
    'size' => 'sm',
    'showLabel' => false,
    'label' => null,
])

@php
    $sizeClasses = match ($size) {
        'xs' => 'btn-xs text-xs',
        'sm' => 'btn-sm text-xs',
        'md' => 'btn-md text-sm',
        default => 'btn-sm text-xs',
    };
@endphp

<div
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}
    x-data="tallstackui_darkTheme()"
    x-init="init()"
>
    <x-ts-dropdown position="bottom-end">
        <x-slot:action>
            <button
                type="button"
                x-on:click="show = ! show"
                class="btn btn-ghost {{ $sizeClasses }} rounded-full font-bold tracking-wider uppercase"
                aria-label="{{ __('common.theme.switch') }}"
            >
                <x-ts-icon name="sun" class="size-4 opacity-60" x-show="! darkTheme" x-cloak />
                <x-ts-icon name="moon" class="size-4 opacity-60" x-show="darkTheme" x-cloak />
                <span x-show="! darkTheme" x-cloak>{{ __('common.theme.light') }}</span>
                <span x-show="darkTheme" x-cloak>{{ __('common.theme.dark') }}</span>
            </button>
        </x-slot:action>

        <div class="min-w-40">
            <x-ts-dropdown.items
                :text="__('common.theme.light')"
                icon="sun"
                x-on:click="
                    mode = 'light';
                    $dispatch('theme', { mode: 'light' });
                    show = false;
                "
                ::active="mode === 'light'"
            />
            <x-ts-dropdown.items
                :text="__('common.theme.dark')"
                icon="moon"
                x-on:click="
                    mode = 'dark';
                    $dispatch('theme', { mode: 'dark' });
                    show = false;
                "
                ::active="mode === 'dark'"
            />
            <x-ts-dropdown.items
                :text="__('common.theme.system')"
                icon="adjustments-horizontal"
                x-on:click="
                    mode = 'system';
                    $dispatch('theme', {
                        mode: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light',
                    });
                    show = false;
                "
                ::active="mode === 'system'"
            />
        </div>
    </x-ts-dropdown>
</div>
