<div {{ $attributes->merge(['class' => 'inline-flex items-center']) }}>
    <x-ts-dropdown position="bottom-end">
        <x-slot:action>
            <button
                type="button"
                x-on:click="show = ! show"
                class="hover:bg-base-200 flex cursor-pointer items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold tracking-wider uppercase transition dark:hover:bg-neutral-800"
                aria-label="{{ __('common.language.switch') }}"
            >
                <span class="text-sm leading-none" aria-hidden="true">{{ $locale === 'id' ? '🇮🇩' : '🇬🇧' }}</span>
                <span aria-hidden="true">{{ strtoupper($locale) }}</span>
            </button>
        </x-slot:action>

        <div class="min-w-40">
            <x-ts-dropdown.items wire:click="changeLocale('id')">
                <span class="inline-flex items-center gap-2">
                    <span class="text-base leading-none" aria-hidden="true">🇮🇩</span>
                    <span>{{ __('common.language.indonesian') }}</span>
                </span>
            </x-ts-dropdown.items>
            <x-ts-dropdown.items wire:click="changeLocale('en')">
                <span class="inline-flex items-center gap-2">
                    <span class="text-base leading-none" aria-hidden="true">🇬🇧</span>
                    <span>{{ __('common.language.english') }}</span>
                </span>
            </x-ts-dropdown.items>
        </div>
    </x-ts-dropdown>
</div>
