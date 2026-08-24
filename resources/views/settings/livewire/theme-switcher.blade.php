@props(['theme' => 'system'])

<x-ts-dropdown position="bottom-end">
    <x-slot:action>
        <button class="btn btn-ghost btn-sm btn-circle" aria-label="{{ __('common.theme.switch') }}">
            @if ($theme === 'light')
                <x-ts-icon name="sun" class="size-5" aria-hidden="true" />
            @elseif ($theme === 'dark')
                <x-ts-icon name="moon" class="size-5" aria-hidden="true" />
            @else
                <x-ts-icon name="computer-desktop" class="size-5" aria-hidden="true" />
            @endif
        </button>
    </x-slot:action>

    <x-ts-dropdown.items text="{{ __('common.light') }}" icon="sun" wire:click="setTheme('light')" />
    <x-ts-dropdown.items text="{{ __('common.dark') }}" icon="moon" wire:click="setTheme('dark')" />
    <x-ts-dropdown.items text="{{ __('common.system') }}" icon="computer-desktop" wire:click="setTheme('system')" />
</x-ts-dropdown>
