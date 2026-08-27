@props([
    'sticky' => true,
    'container' => true,
    'transparent' => false,
    'brand' => true,
])

{{-- Modern responsive Navbar — props-driven, no hardcoded menu items. --}}
<nav
    aria-label="{{ __('common.navigation') }}"
    {{ $attributes->merge([
        'class' => collect([
            'flex h-16 items-center justify-between gap-4 border-b px-4 sm:px-6 lg:px-8',
            $transparent ? 'bg-transparent border-transparent' : 'bg-base-100 border-base-content/10 backdrop-blur supports-[backdrop-filter]:bg-base-100/80',
            $sticky ? 'sticky top-0 z-30' : '',
        ])->filter()->implode(' '),
    ]) }}
    x-data="{ mobileOpen: false }"
>
    {{-- Left: brand + navigation links (slot) --}}
    <div class="flex items-center gap-6 min-w-0">
        @if ($brand)
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 shrink-0" aria-label="{{ brand('name') }}">
                <x-ui::ui.logo size="6" />
                <span class="hidden font-bold tracking-tight sm:inline">{{ brand('name') }}</span>
            </a>
        @endif

        {{-- Desktop nav links --}}
        <div class="hidden lg:flex items-center gap-1">
            {{ $slot }}
        </div>
    </div>

    {{-- Right: actions --}}
    <div class="flex items-center gap-2 sm:gap-3">
        @isset($actions)
            <div class="hidden md:flex items-center gap-2">
                {{ $actions }}
            </div>
        @endisset

        {{-- Default actions if no slot provided --}}
        @if (! isset($actions))
            <div class="hidden md:flex items-center gap-2">
                <x-ui::ui.theme-switch size="sm" />
                <livewire:settings.lang-switcher />
            </div>
        @endif

        {{ $actionsMobile ?? '' }}

        {{-- Mobile toggle --}}
        <button
            type="button"
            @click="mobileOpen = !mobileOpen"
            class="btn btn-ghost btn-sm lg:hidden"
            :aria-expanded="mobileOpen.toString()"
            aria-controls="navbar-mobile"
            aria-label="{{ __('common.menu') }}"
        >
            <x-ts-icon name="bars-3" class="size-5" x-show="!mobileOpen" />
            <x-ts-icon name="x-mark" class="size-5" x-show="mobileOpen" x-cloak />
        </button>
    </div>

    {{-- Mobile panel --}}
    <div
        x-show="mobileOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        x-cloak
        id="navbar-mobile"
        class="absolute left-0 right-0 top-full border-t bg-base-100 border-base-content/10 p-4 shadow-lg lg:hidden"
        @click.outside="mobileOpen = false"
    >
        <div class="flex flex-col gap-2">
            {{ $slot }}
            <div class="border-base-content/10 mt-2 flex items-center justify-between border-t pt-3">
                <div class="flex items-center gap-2">
                    <x-ui::ui.theme-switch size="sm" />
                    <livewire:settings.lang-switcher />
                </div>
                @isset($mobileExtra)
                    {{ $mobileExtra }}
                @endisset
            </div>
        </div>
    </div>
</nav>
