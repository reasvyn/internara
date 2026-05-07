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
            <livewire:core.theme-switcher />
        </div>
    @endif

    {{-- Language Switcher --}}
    @if($showLanguage)
        <div class="hidden md:block">
            <livewire:core.language-switcher />
        </div>
    @endif

    @auth
        {{-- Notification Bell --}}
        @if($showNotifications)
            <div class="relative">
                <livewire:notification.notification-bell />
            </div>
        @endif

        {{-- User Dropdown --}}
        @if($showUser)
            <div class="divider divider-horizontal mx-0.5 sm:mx-1 opacity-20 h-6 sm:h-8 self-center"></div>

            <x-mary-dropdown right>
                <x-slot:trigger>
                    <button class="flex items-center gap-2 sm:gap-3 text-left group focus:outline-none min-h-[44px] p-1 rounded-xl hover:bg-base-200/50 transition-colors">
                        <div class="flex flex-col items-end hidden lg:block">
                            <span class="text-xs font-black tracking-tight leading-none group-hover:text-primary transition-colors">{{ auth()->user()->name }}</span>
                            <span class="text-[9px] uppercase tracking-widest font-bold opacity-40 mt-1">{{ auth()->user()->getRoleNames()->first() ?? 'User' }}</span>
                        </div>
                        <div class="relative">
                            <x-mary-avatar
                                :title="auth()->user()->name"
                                class="size-9 sm:size-10 rounded-xl sm:rounded-2xl shadow-md ring-2 ring-transparent group-hover:ring-primary/50 transition-all duration-300"
                            />
                            <div class="absolute -bottom-0.5 sm:-bottom-1 -right-0.5 sm:-right-1 size-2.5 sm:size-3 rounded-full bg-success border-2 border-base-100"></div>
                        </div>
                    </button>
                </x-slot:trigger>

                <div class="px-3 sm:px-4 py-2 sm:py-3 border-b border-base-content/5 mb-1 bg-base-200/30">
                    <p class="text-xs sm:text-sm font-bold">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] truncate opacity-50">{{ auth()->user()->email }}</p>
                </div>

                <x-mary-menu-item :title="__('profile.title')" icon="o-user" link="{{ route('profile') }}" wire:navigate class="rounded-xl mx-1" />

                <x-mary-menu-separator />

                <x-mary-menu-item :title="__('auth.logout')" icon="o-power"
                    class="text-error hover:bg-error/10 hover:text-error rounded-xl mx-1"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();" />

                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                    @csrf
                </form>
            </x-mary-dropdown>
        @endif
    @endauth
</div>
