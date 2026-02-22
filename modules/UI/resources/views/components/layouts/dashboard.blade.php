@props([
    'title' => null,
    'recordTitle' => null,
])

<x-ui::layouts.base :$title body-class="bg-base-300 overflow-hidden">
    <div class="flex h-screen flex-col overflow-hidden">
        {{-- 1. Full Width Navbar (Top) --}}
        <x-ui::navbar class="z-50 flex-none">
            <x-slot:hamburger>
                <label for="main-drawer" class="btn btn-ghost btn-sm btn-circle lg:hidden mr-2" aria-label="{{ __('ui::common.open_menu') }}">
                    <x-ui::icon name="tabler.menu-2" class="size-6" />
                </label>
            </x-slot:hamburger>
        </x-ui::navbar>

        {{-- 2. Layout Container (Bottom) --}}
        <div class="drawer lg:drawer-open flex-1 overflow-hidden">
            <input id="main-drawer" type="checkbox" class="drawer-toggle" />
            
            {{-- Main Content Area --}}
            <div class="drawer-content flex flex-col overflow-y-auto custom-scrollbar bg-base-300">
                <main id="main-content" class="flex-1">
                    <div class="py-8 px-4 sm:px-6 lg:px-10 max-w-7xl mx-auto">
                        {{-- Optional Record Title --}}
                        @if($recordTitle)
                            <div class="mb-10">
                                <h1 class="text-4xl font-black tracking-tight text-base-content">{{ $recordTitle }}</h1>
                            </div>
                        @endif

                        {{-- Page Content --}}
                        <div class="space-y-8 pb-20">
                            {{ $slot }}
                        </div>
                    </div>
                </main>

                {{-- Dashboard Footer --}}
                <footer class="py-6 px-10 flex-none">
                    <x-ui::app-credit />
                </footer>
            </div> 

            {{-- Sidebar Area --}}
            <div class="drawer-side z-40 h-full">
                <label for="main-drawer" aria-label="close sidebar" class="drawer-overlay"></label> 
                {{-- Sidebar is placed below navbar on desktop because its parent (drawer) is below navbar --}}
                <x-ui::sidebar class="h-full border-t border-base-300 lg:border-t-0 shadow-xl lg:shadow-none" />
            </div>
        </div>
    </div>
</x-ui::layouts.base>
