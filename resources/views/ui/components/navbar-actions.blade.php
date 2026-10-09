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
        <div class="hidden md:block">
            <x-setting::locale.theme-switch />
        </div>
    @endif

    {{-- Language Switcher --}}
    @if ($showLanguage)
        <div class="hidden md:block">
            <livewire:setting.locale.lang-switch />
        </div>
    @endif

    @auth
        {{-- Notification Bell --}}
        @if ($showNotifications)
            <div class="relative">
                <livewire:user.notify.notification-bell />
            </div>
        @endif
        {{-- User Dropdown --}}
        @if ($showUser)
            <div class="dark:bg-dark-700 h-6 w-px self-center bg-gray-200"></div>
            <x-ts-dropdown position="bottom-end">
                <x-slot:action>
                    <button
                        type="button"
                        class="focus:ring-primary dark:text-dark-200 dark:hover:bg-dark-700 flex items-center gap-2 rounded-lg p-1.5 text-gray-700 transition-colors hover:bg-gray-100 focus:ring-2 focus:outline-none"
                        x-on:click="show = ! show"
                        aria-label="{{ auth()->user()->name }}"
                    >
                        <span class="hidden text-sm font-medium sm:inline">{{ auth()->user()->name }}</span>
                        <x-ui::components.avatar :user="auth()->user()" size="size-8" />
                    </button>
                </x-slot:action>

                <div class="dark:border-dark-700 dark:bg-dark-800 w-56 rounded-xl border border-gray-200 bg-white p-1.5 shadow-lg">
                    <div class="dark:bg-dark-700/50 mb-1 rounded-lg bg-gray-50 px-3 py-3">
                        <p class="dark:text-dark-100 text-sm font-semibold text-gray-900">{{ auth()->user()->name }}</p>
                        <p class="dark:text-dark-400 truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
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
