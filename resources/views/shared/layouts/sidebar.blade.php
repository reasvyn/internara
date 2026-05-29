@props(['items' => []])

<div class="drawer-side z-[60]">
    <label for="main-drawer" aria-label="close sidebar" class="drawer-overlay"></label>

    <aside class="bg-base-100 min-h-screen w-64 border-r border-base-content/10 flex flex-col">
        <div class="h-16 px-6 border-b border-base-content/10 flex items-center shrink-0">
            <a wire:navigate href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <x-shared::ui.brand size="md" :with-tagline="false" :invert="false" />
            </a>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-6 space-y-6">
            @auth
                @foreach(config('menu.groups') as $group)
                    @if(auth()->user()->hasRole($group['roles']))
                        <div>
                            <h3 class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-wider text-base-content/30">{{ __($group['title']) }}</h3>
                            <ul class="space-y-0.5">
                                @foreach($group['items'] as $item)
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
                                    @if(auth()->user()->hasRole($itemRoles))
                                    <li>
                                        <a wire:navigate href="{{ $url }}"
                                           @class([
                                               'flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors',
                                               'bg-primary/10 text-primary font-medium' => $active,
                                               'text-base-content/60 hover:bg-base-200 hover:text-base-content' => !$active,
                                           ])>
                                            <x-mary-icon :name="$item['icon']" class="size-4 shrink-0" />
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
        <div class="md:hidden border-t border-base-content/10 p-3 flex items-center justify-between">
            <livewire:shared.theme-switcher />
            <livewire:shared.lang-switcher />
        </div>
    </aside>
</div>
