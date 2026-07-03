@props(['items' => []])

<div class="drawer-side z-[60]">
    <label class="drawer-overlay" for="main-drawer" aria-label="close sidebar"></label>

    <aside class="bg-base-100 border-base-content/10 flex min-h-screen w-64 flex-col border-r">
        <div class="border-base-content/10 flex h-16 shrink-0 items-center border-b px-6">
            <a class="flex items-center gap-3" wire:navigate href="{{ route('dashboard') }}">
                <x-core::ui.brand size="md" :with-tagline="false" :invert="false" />
            </a>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-6">
            @auth
                @foreach (config('menu.groups') as $group)
                    @if (auth()->user()->hasRole($group['roles']))
                        <div>
                            <h3 class="text-base-content/30 mb-2 px-3 text-[10px] font-semibold uppercase tracking-wider">
                                {{ __($group['title']) }}
                            </h3>
                            <ul class="space-y-0.5">
                                @foreach ($group['items'] as $item)
                                    @php
                                        $itemRoles = $item['roles'] ?? $group['roles'];
                                        $active = request()->routeIs($item['route'] . '*');
                                        $url = '#';
                                        try {
                                            if (Route::has($item['route'])) {
                                                $url = route($item['route']);
                                            }
                                        } catch (\Throwable) {
                                            $url = '#';
                                        }
                                    @endphp
                                    @if (auth()->user()->hasRole($itemRoles))
                                        <li>
                                            <a wire:navigate href="{{ $url }}" @class ([
                                                'flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors',
                                                'bg-primary/10 text-primary font-medium' => $active,
                                                'text-base-content/60 hover:bg-base-200 hover:text-base-content' => !$active,
                                            ])>
                                                <x-mary-icon class="size-4 shrink-0" :name="$item['icon']" />
                                                <span>{{ __($item['label']) }}</span>
                                            </a>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            @endauth
        </nav>

        {{-- Mobile Switchers --}}
        <div class="border-base-content/10 flex items-center justify-between border-t p-3 md:hidden">
            <livewire:settings.livewire.theme-switcher />
            <livewire:settings.livewire.lang-switcher />
        </div>
    </aside>
</div>
