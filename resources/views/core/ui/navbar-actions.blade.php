@props([
    'showTheme' => true,
    'showLanguage' => true,
    'showNotifications' => true,
    'showUser' => true,
])

<div {{ $attributes->merge([
    'class' => 'flex items-center gap-2 sm:gap-3 lg:gap-4',
]) }}>
    {{-- Theme Switcher --}}
    @if ($showTheme)
        <div class="bg-base-200/50 border-base-content/5 hidden items-center gap-1 rounded-xl border p-1 md:flex">
            <livewire:settings.livewire.theme-switcher />
        </div>
    @endif

    {{-- Language Switcher --}}
    @if ($showLanguage)
        <div class="hidden md:block">
            <livewire:settings.livewire.lang-switcher />
        </div>
    @endif

    @auth
        {{-- Notification Bell --}}
        @if ($showNotifications)
            <div class="relative">
                <livewire:user.notifications.livewire.notification-bell />
            </div>
        @endif
        {{-- User Dropdown --}}
        @if ($showUser)
            <div class="bg-base-content/10 h-6 w-px self-center"></div>
            <x-mary-dropdown right>
                <x-slot:trigger>
                    <button class="btn btn-ghost btn-sm flex items-center gap-2 rounded-lg px-2">
                        <span class="hidden text-sm font-medium sm:inline">{{ auth()->user()->name }}</span>
                        <x-core::ui.avatar :user="auth()->user()" size="size-8" />
                    </button>
                </x-slot:trigger>

                <div class="bg-base-100 border-base-content/10 w-56 rounded-xl border p-1.5 shadow-lg">
                    <div class="bg-base-200/50 mb-1 rounded-lg px-3 py-3">
                        <p class="text-sm font-semibold">{{ auth()->user()->name }}</p>
                        <p class="text-base-content/50 truncate text-xs">{{ auth()->user()->email }}</p>
                    </div>

                    <x-mary-menu-item class="rounded-lg py-2 !text-sm" :title="__('profile.title')" icon="o-user"
                        link="{{ route('profile') }}" wire:navigate />

                    <x-mary-menu-item class="text-error hover:bg-error/10 rounded-lg py-2 !text-sm" :title="__('auth.logout')"
                        icon="o-power"
                        onclick="
                            event.preventDefault()
                            document.getElementById('logout-form').submit()
                        " />

                    <form class="hidden" id="logout-form" action="{{ route('logout') }}" method="POST">
                        @csrf
                    </form>
                </div>
            </x-mary-dropdown>
        @endif
    @endauth
</div>
