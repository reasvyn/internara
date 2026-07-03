@props(['lang' => 'en'])

<x-mary-dropdown>
    <x-slot:trigger>
        <button class="btn btn-ghost btn-sm btn-circle" aria-label="{{ __('common.language.switch') }}">
            <span class="text-xs font-bold uppercase tracking-wider" aria-hidden="true">{{ $locale }}</span>
        </button>
    </x-slot:trigger>

    <x-mary-menu-item :title="__('common.language.indonesian')" wire:click="setLocale('id')" :active="$locale === 'id'" />
    <x-mary-menu-item :title="__('common.language.english')" wire:click="setLocale('en')" :active="$locale === 'en'" />
</x-mary-dropdown>
