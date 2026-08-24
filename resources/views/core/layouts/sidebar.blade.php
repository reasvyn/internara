@props(['items' => []])

<x-ts-side-bar navigate smart collapsible>
    <x-slot:brand>
        <div class="flex h-16 shrink-0 items-center px-6">
            <a class="flex items-center gap-3" wire:navigate href="{{ route('dashboard') }}">
                <x-core::ui.brand size="md" :with-tagline="false" :invert="false" />
            </a>
        </div>
    </x-slot:brand>

    @auth
        @foreach (config('menu.groups') as $group)
            @if (auth()->user()->hasRole($group['roles']))
                <x-ts-side-bar.separator :text="__($group['title'])" />
                @foreach ($group['items'] as $item)
                    @php
                        $itemRoles = $item['roles'] ?? $group['roles'];
                        $disabled = $item['disabled'] ?? false;
                        $active = ! $disabled && request()->routeIs($item['route']);
                        $url = '#';
                        if (! $disabled) {
                            try {
                                if (Route::has($item['route'])) {
                                    $url = route($item['route']);
                                }
                            } catch (\Throwable) {
                                $url = '#';
                            }
                        }
                        $rawIcon = $item['icon'] ?? null;
                        $icon = $rawIcon ? (str_starts_with($rawIcon, 'o-') ? substr($rawIcon, 2) : (str_starts_with($rawIcon, 's-') ? substr($rawIcon, 2) : $rawIcon)) : null;
                    @endphp
                    @if (auth()->user()->hasRole($itemRoles))
                        @if ($disabled)
                            <div class="flex items-center gap-3 px-3 py-2 text-sm opacity-40">
                                @if ($icon)
                                    <x-ts-icon :name="$icon" class="size-4 shrink-0" />
                                @endif
                                <span>{{ __($item['label']) }}</span>
                            </div>
                        @else
                            <x-ts-side-bar.item
                                :text="__($item['label'])"
                                :route="$url"
                                :icon="$icon"
                                :current="$active"
                            />
                        @endif
                    @endif
                @endforeach
            @endif
        @endforeach
    @endauth

    <x-slot:footer>
        <div class="flex items-center justify-between p-3 md:hidden">
            <div x-data="tallstackui_darkTheme()" class="inline-flex"><x-core::ui.theme-switch /></div>
            <livewire:settings.lang-switcher />
        </div>
    </x-slot:footer>
</x-ts-side-bar>
