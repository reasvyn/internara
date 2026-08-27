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
    @toggle-sidebar.window="open = !open"
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
        class="fixed inset-y-0 left-0 z-40 w-64 shrink-0 border-r bg-base-100 border-base-content/10 transition-transform duration-200 lg:static lg:translate-x-0"
        id="app-sidebar"
        role="navigation"
        aria-label="{{ __('common.navigation') }}"
    >
        <x-slot:brand>
            @if ($brand)
                <div class="flex h-16 shrink-0 items-center gap-3 px-6 border-b border-base-content/10">
                    <a class="flex items-center gap-3 min-w-0" wire:navigate href="{{ route('dashboard') }}">
                        <x-ui::components.brand size="md" :with-tagline="false" :invert="false" />
                    </a>
                    <button type="button" @click="open = false" class="btn btn-ghost btn-xs lg:hidden ml-auto" aria-label="{{ __('common.close') }}">
                        <x-ts-icon name="x-mark" class="size-4" />
                    </button>
                </div>
            @endif
        </x-slot:brand>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6" aria-label="{{ __('common.main_navigation') }}">
            @auth
                @foreach ($groups as $group)
                    @if (auth()->user()->hasRole($group['roles'] ?? []))
                        <div>
                            @if (! empty($group['title']))
                                <x-ts-side-bar.separator :text="__($group['title'])" />
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
                                                <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm opacity-40 cursor-not-allowed" aria-disabled="true">
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
            <div class="border-t border-base-content/10 p-3 space-y-3">
                {{-- Mobile-only: theme + lang --}}
                <div class="flex items-center justify-between gap-2 md:hidden">
                    <x-ui::components.theme-switch size="sm" />
                    <livewire:settings.lang-switcher />
                </div>

                {{-- Desktop: collapse hint --}}
                @if ($collapsible)
                    <div class="hidden lg:flex items-center justify-between text-xs text-base-content/50">
                        <span>{{ __('common.collapse') }}</span>
                        <x-ts-icon name="chevron-double-left" class="size-3.5" />
                    </div>
                @endif

                {{ $footer ?? '' }}
            </div>
        </x-slot:footer>
    </x-ts-side-bar>
</div>
