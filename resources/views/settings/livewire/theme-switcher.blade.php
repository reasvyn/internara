<x-mary-dropdown>
    <x-slot:trigger>
        <button class="btn btn-ghost btn-sm btn-circle" aria-label="{{ __('common.theme.switch') }}">
            @if($theme === 'light')
                <x-mary-icon name="o-sun" class="size-5" aria-hidden="true" />
            @elseif($theme === 'dark')
                <x-mary-icon name="o-moon" class="size-5" aria-hidden="true" />
            @else
                <x-mary-icon name="o-computer-desktop" class="size-5" aria-hidden="true" />
            @endif
        </button>
    </x-slot:trigger>

    <x-mary-menu-item title="{{ __('common.light') }}" icon="o-sun"
        wire:click="setTheme('light')"
        :active="$theme === 'light'"
    />
    <x-mary-menu-item title="{{ __('common.dark') }}" icon="o-moon"
        wire:click="setTheme('dark')"
        :active="$theme === 'dark'"
    />
    <x-mary-menu-item title="{{ __('common.system') }}" icon="o-computer-desktop"
        wire:click="setTheme('system')"
        :active="$theme === 'system'"
    />
</x-mary-dropdown>
