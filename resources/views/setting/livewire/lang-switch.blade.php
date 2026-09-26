<div {{ $attributes->merge(['class' => 'inline-flex items-center']) }}>
    <x-ts-dropdown position="bottom-end">
        <x-slot:action>
            <button
                type="button"
                x-on:click="show = ! show"
                class="hover:bg-base-200 flex cursor-pointer items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold tracking-wider uppercase transition dark:hover:bg-neutral-800"
                aria-label="{{ __('common.language.switch') }}"
            >
                <x-ts-icon name="globe-alt" class="size-3.5 opacity-60" />
                <span aria-hidden="true">{{ strtoupper($locale) }}</span>
            </button>
        </x-slot:action>

        <div class="min-w-40">
            <x-ts-dropdown.items
                :text="__('common.language.indonesian')"
                icon="globe-alt"
                wire:click="changeLocale('id')"
            />
            <x-ts-dropdown.items
                :text="__('common.language.english')"
                icon="globe-alt"
                wire:click="changeLocale('en')"
            />
        </div>
    </x-ts-dropdown>
</div>
