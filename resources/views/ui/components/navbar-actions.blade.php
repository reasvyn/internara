@props([
    'showTheme' => true,
    'showLanguage' => true,
    'showNotifications' => true,
    'showUser' => true,
])

<div {{
    $attributes->merge([
        'class' => 'flex items-center gap-2 sm:gap-3 lg:gap-4',
    ])
}}>
    {{-- Theme Switcher --}}
    @if ($showTheme)
        <div class="hidden md:block" x-data="tallstackui_darkTheme()">
            <x-ui::components.theme-switch />
        </div>
    @endif

    {{-- Language Switcher --}}
    @if ($showLanguage)
        <div class="hidden md:block">
            <livewire:settings.lang-switcher />
        </div>
    @endif

    @auth
        {{-- Notification Bell --}}
        @if ($showNotifications)
            <div class="relative">
                <livewire:user.notifications.notification-bell />
            </div>
        @endif
        {{-- User Dropdown --}}
        @if ($showUser)
            <div class="bg-base-content/10 h-6 w-px self-center"></div>
            <x-ts-dropdown position="bottom-end">
                <x-slot:action>
                    <button
                        class="btn btn-ghost btn-sm flex items-center gap-2 rounded-lg px-2"
                        x-on:click="show = ! show"
                    >
                        <span class="hidden text-sm font-medium sm:inline">{{ auth()->user()->name }}</span>
                        <x-ui::components.avatar :user="auth()->user()" size="size-8" />
                    </button>
                </x-slot:action>

                <div class="bg-base-100 border-base-content/10 w-56 rounded-xl border p-1.5 shadow-lg">
                    <div class="bg-base-200/50 mb-1 rounded-lg px-3 py-3">
                        <p class="text-sm font-semibold">{{ auth()->user()->name }}</p>
                        <p class="text-base-content/50 truncate text-xs">{{ auth()->user()->email }}</p>
                    </div>

                    <x-ts-dropdown.items
                        :text="__('profile.title')"
                        icon="user"
                        :href="route('profile')"
                        wire:navigate
                    />

                    <x-ts-dropdown.items
                        :text="__('auth.logout')"
                        icon="power"
                        @click.prevent="document.getElementById('logout-form').submit()"
                    />

                    <form class="hidden" id="logout-form" action="{{ route('logout') }}" method="POST">
                        @csrf
                    </form>
                </div>
            </x-ts-dropdown>
        @endif
    @endauth
</div>
