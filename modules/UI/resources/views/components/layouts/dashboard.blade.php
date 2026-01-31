@props(['title' => null])

<x-ui::layouts.base :$title>
    {{-- Main Navbar --}}
    <x-ui::navbar sticky full-width>
        <x-slot:hamburger>
            <label for="main-drawer" class="lg:hidden mr-3" aria-label="{{ __('Open menu') }}">
                <x-ui::icon name="tabler.menu-2" class="cursor-pointer" aria-hidden="true" />
            </label>
        </x-slot:hamburger>
    </x-ui::navbar>

    {{-- Main Container --}}
    <x-ui::main full-width>
        {{-- Sidebar --}}
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100 border-r border-base-200">
            {{-- Sidebar Menu --}}
            <x-mary-menu activate-by-route>
                <x-ui::slot-render name="sidebar.menu" />
            </x-mary-menu>
        </x-slot:sidebar>

        {{-- Main Content --}}
        <div id="main-content">
            {{ $slot }}
        </div>
    </x-ui::main>
</x-ui::layouts.base>
