@props([
    'items' => null, // array of groups or null to use config('menu.groups')
    'collapsible' => true,
    'brand' => true,
])

@php
    $groups = $items ?? config('menu.groups', []);
@endphp

{{-- Modern responsive Sidebar — props-driven, config fallback, no hardcoded items.
     TallStackUI pattern: collapsible diaktifkan via prop :collapsible, store $store['tsui.side-bar']
     (open/collapsed/mobile) di-manage oleh collapse.js + Alpine.store, dan mobile drawer via
     tallStackUiMenuMobile dari <x-ts-layout> (tallstackui_layout). Jangan override positioning/width
     dengan class manual; biarkan desktop.sizes.expanded/collapsed milik TallStackUI yang mengatur rail.
--}}
<x-ts-side-bar
    navigate
    smart
    :collapsible="$collapsible"
    id="app-sidebar"
    role="navigation"
    aria-label="{{ __('common.navigation') }}"
>
    <x-slot:brand>
        @if ($brand)
            <div class="dark:border-dark-700 flex h-16 shrink-0 items-center gap-3 border-b border-gray-200 px-6">
                <a class="flex min-w-0 items-center gap-3" wire:navigate href="{{ route('dashboard') }}">
                    <x-ui::components.brand size="md" :with-tagline="false" :invert="false" />
                </a>
            </div>
        @endif
    </x-slot:brand>

    @if ($collapsible)
        <x-slot:brand-collapsed>
            <div class="dark:border-dark-700 flex h-16 shrink-0 items-center justify-center border-b border-gray-200 px-2">
                <a wire:navigate href="{{ route('dashboard') }}" aria-label="{{ brand('name') }}">
                    <x-ui::components.logo size="7" />
                </a>
            </div>
        </x-slot:brand-collapsed>
    @endif

    @auth
        @foreach ($groups as $group)
            @if (auth()->user()->hasRole($group['roles'] ?? []))
                <div class="space-y-1">
                    @if (! empty($group['title']))
                        <x-ts-side-bar.separator
                            :text="__($group['title'])"
                            class="dark:text-dark-100 font-semibold text-gray-900"
                        />
                    @endif
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
                                    class="dark:text-dark-200 text-gray-700 hover:text-gray-900 dark:hover:text-white"
                                />
                            @endif
                        @endif
                    @endforeach
                </div>
            @endif
        @endforeach
    @endauth

    {{ $slot ?? '' }}

    <x-slot:footer>
        <div class="dark:border-dark-700 space-y-3 border-t border-gray-200 p-3">
            {{-- Mobile-only: theme + lang --}}
            <div class="flex items-center justify-between gap-2 md:hidden">
                <x-setting::locale.theme-switch size="sm" />
                <livewire:setting.locale.lang-switch />
            </div>

            {{-- Desktop: collapse toggle — WAJIB pakai store.toggle(), bukan mutasi collapsed getter.
                     collapsed = getter (collapsible && !open && !mobile). Mutasi getter tidak persist & tidak flip open.
                     toggle() flips open + localStorage('side-bar') sehingga rail benar-benar collapses/expands
                     dan header toggler (di <x-ts-layout.header>) sinkron. Pakai x-bind untuk aria & class. --}}
            @if ($collapsible)
                <button
                    type="button"
                    x-on:click="$store['tsui.side-bar'].toggle()"
                    x-bind:aria-expanded="! $store['tsui.side-bar'].collapsed"
                    class="dark:text-dark-400 dark:hover:bg-dark-700 dark:hover:text-dark-200 hidden w-full items-center rounded-lg px-2 py-1.5 text-xs text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-900 md:flex"
                    x-bind:class="$store['tsui.side-bar'].collapsed ? 'justify-center' : 'justify-between'"
                    aria-label="{{ __('common.collapse') }}"
                >
                    <span x-show="! $store['tsui.side-bar'].collapsed" x-cloak>{{ __('common.collapse') }}</span>
                    <span
                        class="inline-flex transition-transform duration-200"
                        x-bind:class="$store['tsui.side-bar'].collapsed ? 'rotate-180' : ''"
                    >
                        <x-ts-icon name="chevron-double-left" class="size-2.5" />
                    </span>
                </button>
            @endif

            {{ $footer ?? '' }}
        </div>
    </x-slot:footer>
</x-ts-side-bar>
