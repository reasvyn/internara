@props([
    'showTheme' => true,
    'showLanguage' => true,
    'showNotifications' => true,
    'showUser' => true,
])

<div {{ $attributes->merge(['class' => 'flex items-center gap-2 sm:gap-3 lg:gap-4']) }}>
    {{-- Theme Switcher --}}
    @if($showTheme)
        <div class="hidden md:flex items-center gap-1 bg-base-200/50 p-1 rounded-xl border border-base-content/5">
            <livewire:shared.theme-switcher />
        </div>
    @endif

    {{-- Language Switcher --}}
    @if($showLanguage)
        <div class="hidden md:block">
            <livewire:shared.lang-switcher />
        </div>
    @endif

    @auth
        {{-- Notification Bell --}}
        @if($showNotifications)
            <div class="relative">
                <livewire:user.notification-bell />
            </div>
        @endif

        {{-- User Dropdown --}}
        @if($showUser)
            <div class="w-px h-6 bg-base-content/10 self-center"></div>

            <x-mary-dropdown right>
                <x-slot:trigger>
                    <button class="flex items-center gap-2 btn btn-ghost btn-sm rounded-lg px-2">
                        <span class="text-sm font-medium hidden sm:inline">{{ auth()->user()->name }}</span>
                        <x-mary-avatar
                            placeholder="{{ auth()->user()->initials() }}"
                            class="size-8"
                        />
                    </button>
                </x-slot:trigger>

                <div class="w-56 p-1.5 bg-base-100 border border-base-content/10 rounded-xl shadow-lg">
                    <div class="px-3 py-3 mb-1 rounded-lg bg-base-200/50">
                        <p class="text-sm font-semibold">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-base-content/50 truncate">{{ auth()->user()->email }}</p>
                    </div>

                    <x-mary-menu-item :title="__('profile.title')" icon="o-user" link="{{ route('profile') }}" wire:navigate class="rounded-lg !text-sm py-2" />

                    <x-mary-menu-item :title="__('auth.logout')" icon="o-power"
                        class="text-error hover:bg-error/10 rounded-lg !text-sm py-2"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();" />

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                        @csrf
                    </form>
                </div>
            </x-mary-dropdown>
        @endif
    @endauth
</div>
