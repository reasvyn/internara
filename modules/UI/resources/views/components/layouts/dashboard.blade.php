@props(['title' => null])

<x-ui::layouts.base :$title body-class="bg-base-300">
    {{-- Main Navbar --}}
    <x-ui::navbar sticky full-width>
        <x-slot:hamburger>
            <label for="main-drawer" class="lg:hidden mr-3" aria-label="{{ __('ui::common.open_menu') }}">
                <x-ui::icon name="tabler.menu-2" class="cursor-pointer size-6" aria-hidden="true" />
            </label>
        </x-slot:hamburger>
    </x-ui::navbar>

    {{-- Main Container --}}
    <x-ui::main full-width>
        {{-- Sidebar --}}
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-200 border-r border-base-300 lg:bg-base-200">
            {{-- Sidebar Menu --}}
            <x-mary-menu activate-by-route class="gap-1 p-2 mt-4">
                <x-ui::slot-render name="sidebar.menu" />
            </x-mary-menu>
        </x-slot:sidebar>

        {{-- Main Content --}}
        <div id="main-content" class="p-6 lg:p-10">
            @if($title)
                <div class="mb-8">
                    <h1 class="text-3xl font-bold tracking-tight text-base-content">{{ $title }}</h1>
                </div>
            @endif

            <div class="space-y-8">
                {{ $slot }}
            </div>
        </div>
    </x-ui::main>
</x-ui::layouts.base>
