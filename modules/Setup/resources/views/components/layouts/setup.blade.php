@props(['title' => null])

<x-ui::layouts.base :$title>
    <div class="drawer min-h-screen">
        <input id="main-drawer" type="checkbox" class="drawer-toggle" />
        
        <div class="drawer-content flex flex-col bg-base-100">
            {{-- Navbar inside drawer-content --}}
            <x-ui::nav {{ $attributes->merge(['class' => 'bg-base-100/80 backdrop-blur-md border-b border-base-200 sticky top-0 z-40 flex-none']) }}>
                <x-slot:hamburger>
                    <label for="main-drawer" class="btn btn-ghost btn-sm btn-circle lg:hidden mr-2" aria-label="{{ __('ui::common.open_menu') }}">
                        <x-ui::icon name="tabler.menu-2" class="size-6" />
                    </label>
                </x-slot:hamburger>

                <x-slot:brand>
                    @if(slot_exists('navbar.brand'))
                        @slotRender('navbar.brand')
                    @else
                        <x-ui::brand />
                    @endif

                    <x-ui::badge 
                        :value="\Illuminate\Support\Str::start(setting('app_version', '0.1.0'), 'v')" 
                        variant="metadata" 
                        class="ml-2" 
                    />
                </x-slot>

                <x-slot:actions>
                    <livewire:ui::language-switcher />
                    <x-ui::theme-toggle />
                </x-slot>
            </x-ui::nav>

            {{-- Main Content --}}
            <main id="main-content" class="flex-1 flex flex-col items-center justify-center p-4">
                <x-honeypot />
                {{ $slot }}
            </main>

            <x-ui::footer class="border-t border-base-200 flex-none mt-auto" />
        </div>

        {{-- Truly Full Screen Drawer Side --}}
        <div class="drawer-side z-50">
            <label for="main-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
            <x-ui::sidebar-default class="h-full" />
        </div>
    </div>
</x-ui::layouts.base>
