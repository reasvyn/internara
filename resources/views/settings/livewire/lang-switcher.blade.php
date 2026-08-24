@props(['lang' => 'en'])

<x-ts-dropdown position="bottom-end">
    <x-slot:action>
        <button class="btn btn-ghost btn-sm btn-circle" aria-label="{{ __('common.language.switch') }}">
            <span class="text-xs font-bold tracking-wider uppercase" aria-hidden="true">{{ $locale }}</span>
        </button>
    </x-slot:action>

    <x-ts-dropdown.items text="{{ __('common.language.indonesian') }}" wire:click="setLocale('id')" />
    <x-ts-dropdown.items text="{{ __('common.language.english') }}" wire:click="setLocale('en')" />
</x-ts-dropdown>
