<div
    {{ $attributes->merge(['class' => 'relative inline-flex items-center']) }}
    x-data="{ open: false }"
    x-on:click.outside="open = false"
>
    <button
        type="button"
        x-on:click="open = ! open"
        x-bind:aria-expanded="open"
        class="hover:bg-base-200 flex cursor-pointer items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold tracking-wider uppercase transition dark:hover:bg-neutral-800"
        aria-haspopup="menu"
        aria-label="{{ __('common.language.switch') }}"
    >
        <span class="text-sm leading-none" aria-hidden="true">{{ $locale === 'id' ? '🇮🇩' : '🇬🇧' }}</span>
        <span aria-hidden="true">{{ strtoupper($locale) }}</span>
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition
        role="menu"
        class="absolute top-full right-0 z-50 mt-1 min-w-40 overflow-hidden rounded-md border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
    >
        <button
            type="button"
            role="menuitem"
            wire:click="changeLocale('id')"
            x-on:click="open = false"
            class="flex w-full cursor-pointer items-center gap-2 px-3 py-2 text-left text-sm text-gray-600 transition-colors hover:bg-gray-100 focus:bg-gray-100 focus:outline-hidden dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:bg-gray-700"
        >
            <span class="text-base leading-none" aria-hidden="true">🇮🇩</span>
            <span>{{ __('common.language.indonesian') }}</span>
        </button>
        <button
            type="button"
            role="menuitem"
            wire:click="changeLocale('en')"
            x-on:click="open = false"
            class="flex w-full cursor-pointer items-center gap-2 px-3 py-2 text-left text-sm text-gray-600 transition-colors hover:bg-gray-100 focus:bg-gray-100 focus:outline-hidden dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:bg-gray-700"
        >
            <span class="text-base leading-none" aria-hidden="true">🇬🇧</span>
            <span>{{ __('common.language.english') }}</span>
        </button>
    </div>
</div>
