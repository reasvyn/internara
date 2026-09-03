@props([
    'items' => null, // array of groups or null to use config('menu.groups')
    'collapsible' => true,
    'brand' => true,
])

@php
    $groups = $items ?? config('menu.groups', []);
@endphp

{{-- Modern responsive Sidebar — props-driven, config fallback, no hardcoded items. --}}
<div
    x-data="{ open: false }"
    @toggle-sidebar.window="open = ! open"
    @keydown.escape.window="open = false"
    class="contents"
>
    {{-- Mobile backdrop --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        @click="open = false"
        class="fixed inset-0 z-30 bg-black/30 backdrop-blur-sm lg:hidden"
        aria-hidden="true"
    ></div>

    {{-- Sidebar panel --}}
    <x-ts-side-bar
        navigate
        smart
        :collapsible="$collapsible"
        x-bind:class="open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        class="bg-base-100 border-base-content/10 fixed inset-y-0 left-0 z-40 w-64 shrink-0 border-r transition-transform duration-200 lg:static lg:translate-x-0"
        id="app-sidebar"
        role="navigation"
        aria-label="{{ __('common.navigation') }}"
    >
        <x-slot:brand>
            @if ($brand)
                <div class="border-base-content/10 flex h-16 shrink-0 items-center gap-3 border-b px-6">
                    <a class="flex min-w-0 items-center gap-3" wire:navigate href="{{ route('dashboard') }}">
                        <x-ui::components.brand size="md" :with-tagline="false" :invert="false" />
                    </a>
                    <button
                        type="button"
                        @click="open = false"
                        class="btn btn-ghost btn-xs ml-auto lg:hidden"
                        aria-label="{{ __('common.close') }}"
                    >
                        <x-ts-icon name="x-mark" class="size-4" />
                    </button>
                </div>
            @endif
        </x-slot:brand>

        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4" aria-label="{{ __('common.main_navigation') }}">
            @auth
                @foreach ($groups as $group)
                    @if (auth()->user()->hasRole($group['roles'] ?? []))
                        <div>
                            @if (! empty($group['title']))
                                <x-ts-side-bar.separator
                                    :text="__($group['title'])"
                                    class="text-base-content font-semibold dark:text-white"
                                />
                            @endif
                            <ul class="space-y-1" role="list">
                                @foreach ($group['items'] ?? [] as $item)
                                    @php
                                        $itemRoles = $item['roles'] ?? $group['roles'] ?? [];
                                        $disabled = $item['disabled'] ?? false;
                                        $active = ! $disabled && request()->routeIs($item['route'] ?? '');
                                        $url = '#';
                                        if (! $disabled && ! empty($item['route'])) {
                                            try {
                                                if (\Illuminate\Support\Facades\Route::has($item['route'])) {
                                                    $url = route($item['route']);
                                                }
                                            } catch (\Throwable) {
                                                $url = '#';
                                            }
                                        }
                                        $rawIcon = $item['icon'] ?? null;
                                        $icon = $rawIcon ? (str_starts_with($rawIcon, 'o-') ? substr($rawIcon, 2) : (str_starts_with($rawIcon, 's-') ? substr($rawIcon, 2) : $rawIcon)) : null;
                                    @endphp
                                    @if (empty($itemRoles) || auth()->user()->hasRole($itemRoles))
                                        <li>
                                            @if ($disabled)
                                                <div
                                                    class="flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2.5 text-sm opacity-40"
                                                    aria-disabled="true"
                                                >
                                                    @if ($icon)
                                                        <x-ts-icon :name="$icon" class="size-4 shrink-0" />
                                                    @endif
                                                    <span class="truncate">{{ __($item['label'] ?? '') }}</span>
                                                </div>
                                            @else
                                                <x-ts-side-bar.item
                                                    :text="__($item['label'] ?? '')"
                                                    :route="$url"
                                                    :icon="$icon"
                                                    :current="$active"
                                                    class="text-base-content hover:text-base-content dark:text-white dark:hover:text-white"
                                                />
                                            @endif
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            @endauth

            {{ $slot }}
        </nav>

        <x-slot:footer>
            <div class="border-base-content/10 space-y-3 border-t p-3">
                {{-- Mobile-only: theme + lang --}}
                <div class="flex items-center justify-between gap-2 md:hidden">
                    <x-ui::components.theme-switch size="sm" />
                    <livewire:settings.lang-switcher />
                </div>

                {{-- Desktop: collapse toggle --}}
                @if ($collapsible)
                    <button
                        type="button"
                        @click="$store['tsui.side-bar'].collapsed = ! $store['tsui.side-bar'].collapsed"
                        class="text-base-content/60 hover:text-base-content hover:bg-base-200/50 dark:hover:bg-dark-700/50 hidden w-full items-center justify-between rounded-lg px-2 py-1.5 text-xs transition-colors lg:flex"
                        :aria-expanded="! $store['tsui.side-bar'].collapsed"
                        aria-label="{{ __('common.collapse') }}"
                    >
                        <span>{{ __('common.collapse') }}</span>
                        <span
                            class="inline-flex transition-transform duration-200"
                            ::class="$store['tsui.side-bar'].collapsed ? 'rotate-180' : ''"
                        >
                            <x-ts-icon name="chevron-double-left" class="size-2.5" />
                        </span>
                    </button>
                @endif

                {{ $footer ?? '' }}
            </div>
        </x-slot:footer>
    </x-ts-side-bar>
</div>
