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

    {{-- About Link --}}
    <a
        wire:navigate
        href="{{ route('about') }}"
        class="text-base-content/60 hover:text-primary hover:bg-base-200 hidden size-9 items-center justify-center rounded-xl transition-colors md:flex"
        title="{{ __('about.title') }}"
        aria-label="{{ __('about.title') }}"
    >
        <x-ts-icon name="information-circle" class="size-5" />
    </a>

    @auth
        {{-- Notification Bell --}}
        @if ($showNotifications)
            <div class="relative">
                <livewire:user.notify.notification-bell />
            </div>
        @endif
        {{-- User Dropdown --}}
        @if ($showUser)
            <div class="bg-base-content/10 h-6 w-px self-center"></div>
            <x-ts-dropdown position="bottom-end">
                <x-slot:action>
                    <button
                        type="button"
                        class="focus:ring-primary text-base-content hover:bg-base-200 flex items-center gap-2 rounded-xl p-1.5 transition-colors focus:ring-2 focus:outline-none"
                        x-on:click="show = ! show"
                        aria-label="{{ auth()->user()->name }}"
                    >
                        <span class="hidden text-sm font-medium sm:inline">{{ auth()->user()->name }}</span>
                        <x-ui::components.avatar :user="auth()->user()" size="size-8" />
                    </button>
                </x-slot:action>

                <div class="border-base-content/10 bg-base-100 w-56 rounded-2xl border p-1.5 shadow-md">
                    <div class="bg-base-200/50 mb-1 rounded-xl px-3 py-2.5">
                        <p class="text-base-content text-sm font-semibold">{{ auth()->user()->name }}</p>
                        <p class="text-base-content/60 truncate text-xs">{{ auth()->user()->email }}</p>
                    </div>

                    <x-ts-dropdown.items
                        :text="__('profile.title')"
                        icon="user"
                        :href="route('profile')"
                        wire:navigate
                    />

                    <x-ts-dropdown.items
                        :text="__('about.title')"
                        icon="information-circle"
                        :href="route('about')"
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
