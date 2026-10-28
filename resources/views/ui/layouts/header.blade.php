@props([
    'header' => null,
    'breadcrumbs' => null, // array of ['label' => '', 'url' => '']
    'sticky' => true,
])

{{--
    TallStackUI Header — mengikuti spec resmi.

    Cara TallStackUI memasang toggler:
    - Sidebar collapsible: <x-ts-side-bar collapsible> meng-set $store['tsui.side-bar'].collapsible = true
      via x-init dan mendaftarkan Alpine.store('tsui.side-bar', {open, mobile, collapsible, collapsed getter, toggle()}).
      collapsed = collapsible && !open && !mobile (rail mode). toggle() flip open + persist localStorage('side-bar').
    - Header toggler DESKTOP: <x-ts-layout.header> otomatis merender tombol
      x-show="$store['tsui.side-bar'].collapsible" x-on:click="$store['tsui.side-bar'].toggle()"
      (collapse.class). Tombol ini HANYA muncul jika sidebar collapsible=true dan di desktop.
    - Header toggler MOBILE: <x-ts-layout.header> merender hamburger
      x-on:click="tallStackUiMenuMobile = !tallStackUiMenuMobile" yang mengontrol drawer
      <x-ts-layout> x-data="tallstackui_layout()" -> tallStackUiMenuMobile. Drawer + backdrop + scroll-lock
      sudah di-handle oleh <x-ts-side-bar> mobile wrapper (x-show="tallStackUiMenuMobile").
    - Jangan dispatch custom event 'toggle-sidebar' atau mutasi $store.collapsed langsung; pakai
      store.toggle() untuk persist dan header/footer harus sinkron via store yang sama.

    Implementasi di sini: wrapper <x-ts-layout.header> menyediakan kedua toggler secara native.
    Konten custom (breadcrumbs, judul, navbar-actions) dipetakan ke slot left/right milik header
    tersebut. Tidak ada header manual lagi.
--}}
<x-ts-layout.header {{ $attributes->merge(['role' => 'banner']) }}>
    {{-- LEFT: breadcrumbs + title (ditaruh di left slot header TallStackUI) --}}
    <x-slot:left>
        <div class="flex min-w-0 flex-col justify-center">
            @if ($breadcrumbs)
                <nav
                    aria-label="Breadcrumb"
                    class="text-base-content/60 mb-0.5 hidden items-center gap-1.5 text-xs sm:flex"
                >
                    @foreach ($breadcrumbs as $crumb)
                        @if (! $loop->last && ! empty($crumb['url']))
                            <a
                                href="{{ $crumb['url'] }}"
                                wire:navigate
                                class="hover:text-primary truncate transition-colors"
                            >{{ $crumb['label'] }}</a>
                            <x-ts-icon name="chevron-right" class="size-3 shrink-0 opacity-40" />
                        @else
                            <span class="text-primary truncate font-medium">{{ $crumb['label'] }}</span>
                        @endif
                    @endforeach
                </nav>
            @endif

            {{-- Mobile brand. The page title lives in the page itself
                 (`ui::components.page-header` / `record-manager`), which owns the single page
                 heading; the shell must not render a competing one. --}}
            <div class="flex items-center gap-2 md:hidden">
                <a wire:navigate href="{{ route('dashboard') }}" aria-label="{{ brand('name') }}">
                    <x-ui::components.logo size="5" />
                </a>
                <span class="text-sm font-bold">{{ brand('name') }}</span>
            </div>
        </div>
    </x-slot:left>

    {{-- RIGHT: actions + navbar-actions --}}
    <x-slot:right>
        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
            @isset($actions)
                <div class="hidden items-center gap-2 sm:flex">{{ $actions }}</div>
            @endisset

            <x-ui::components.navbar-actions
                :show-theme="true"
                :show-language="true"
                :show-notifications="true"
                :show-user="true"
            />

            @isset($actions)
                <div class="sm:hidden">
                    <x-ts-dropdown position="bottom-end">
                        <x-slot:action>
                            <button
                                type="button"
                                class="focus:ring-primary text-base-content/70 hover:bg-base-200 hover:text-base-content flex size-9 items-center justify-center rounded-xl transition-colors focus:ring-2 focus:outline-none"
                                aria-label="{{ __('common.actions.label') }}"
                            >
                                <x-ts-icon name="ellipsis-vertical" class="size-5" />
                            </button>
                        </x-slot:action>
                        <div class="bg-base-100 border-base-content/10 w-48 rounded-2xl border p-2 shadow-md">
                            {{ $actions }}
                        </div>
                    </x-ts-dropdown>
                </div>
            @endisset
        </div>
    </x-slot:right>

    {{ $slot ?? '' }}
</x-ts-layout.header>
