@props(['title' => null])

<x-ui::layouts.base :$title>
    {{-- Main Navbar --}}
    <x-ui::navbar sticky full-width>
        <x-slot:hamburger>
            <label for="main-drawer" class="lg:hidden mr-3" aria-label="{{ __('ui::common.open_menu') }}">
                <x-ui::icon name="tabler.menu-2" class="cursor-pointer size-6" aria-hidden="true" />
            </label>
        </x-slot:hamburger>
    </x-ui::navbar>

    {{-- Main Container --}}
    <x-ui::main full-width class="bg-base-200/50 min-h-screen">
        {{-- Sidebar --}}
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100 border-r border-base-200 lg:bg-base-100">
            {{-- Sidebar Menu --}}
            <x-mary-menu activate-by-route class="gap-1 p-2">
                <x-ui::slot-render name="sidebar.menu" />
            </x-mary-menu>
        </x-slot:sidebar>

        {{-- Main Content --}}
        <div id="main-content" class="p-4 lg:p-8">
            @if($title)
                <div class="mb-8" data-aos="fade-down" data-aos-duration="600">
                    <h1 class="text-3xl font-bold tracking-tight text-base-content">{{ $title }}</h1>
                </div>
            @endif

            <div class="space-y-8" data-aos="fade-up" data-aos-delay="100">
                {{ $slot }}
            </div>
        </div>
    </x-ui::main>
</x-ui::layouts.base>
