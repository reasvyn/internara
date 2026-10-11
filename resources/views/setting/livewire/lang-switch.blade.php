<div
    {{ $attributes->merge(['class' => 'relative inline-flex items-center']) }}
    x-data="{ open: false }"
    x-on:click.outside="open = false"
>
    <button
        type="button"
        x-on:click="open = ! open"
        x-bind:aria-expanded="open"
        class="focus:ring-primary text-base-content hover:bg-base-200 flex cursor-pointer items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold tracking-wider uppercase transition focus:ring-2 focus:outline-none"
        aria-haspopup="menu"
        aria-label="{{ __('common.language.switch') }}"
    >
        <span class="text-sm leading-none" aria-hidden="true">{{ $currentFlag }}</span>
        <span aria-hidden="true">{{ strtoupper($locale) }}</span>
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition
        role="menu"
        class="border-base-content/10 bg-base-100 absolute top-full right-0 z-50 mt-1 min-w-40 overflow-hidden rounded-md border shadow-lg"
    >
        @foreach ($availableLocales as $code => $item)
            <button
                type="button"
                role="menuitem"
                wire:click="changeLocale('{{ $code }}')"
                x-on:click="open = false"
                class="text-base-content hover:bg-base-200 flex w-full cursor-pointer items-center gap-2 px-3 py-2 text-left text-sm transition-colors focus:outline-none"
            >
                <span class="text-base leading-none" aria-hidden="true">{{ $item['flag'] }}</span>
                <span>{{ $item['label'] }}</span>
            </button>
        @endforeach
    </div>
</div>
