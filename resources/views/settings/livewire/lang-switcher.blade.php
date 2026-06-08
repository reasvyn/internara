<x-mary-dropdown>
    <x-slot:trigger>
        <button class="btn btn-ghost btn-sm btn-circle">
            <span class="text-xs font-bold uppercase tracking-wider">{{ $locale }}</span>
        </button>
    </x-slot:trigger>

    <x-mary-menu-item title="Bahasa Indonesia"
        wire:click="setLocale('id')"
        :active="$locale === 'id'"
    />
    <x-mary-menu-item title="English"
        wire:click="setLocale('en')"
        :active="$locale === 'en'"
    />
</x-mary-dropdown>
