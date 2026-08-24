@props(['lang' => 'en'])

<x-ts-dropdown position="bottom-end">
    <x-slot:action>
        <button
            type="button"
            x-on:click="show = ! show"
            class="hover:bg-base-200 rounded-full p-2 text-xs font-bold tracking-wider uppercase transition"
            aria-label="{{ __('common.language.switch') }}"
        >
            <span aria-hidden="true">{{ $locale }}</span>
        </button>
    </x-slot:action>

    <x-ts-dropdown.items text="{{ __('common.language.indonesian') }}" icon="globe-alt" wire:click="setLocale('id')" />
    <x-ts-dropdown.items text="{{ __('common.language.english') }}" icon="globe-alt" wire:click="setLocale('en')" />
</x-ts-dropdown>
