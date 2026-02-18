@props(['title' => null])

<x-ui::layouts.base :$title body-class="bg-base-300 overflow-hidden">
    <div class="flex h-screen flex-col">
        {{-- Main Navbar --}}
        <x-ui::navbar sticky full-width>
            <x-slot:hamburger>
                <label for="main-drawer" class="lg:hidden mr-3" aria-label="{{ __('ui::common.open_menu') }}">
                    <x-ui::icon name="tabler.menu-2" class="cursor-pointer size-6" aria-hidden="true" />
                </label>
            </x-slot:hamburger>
        </x-ui::navbar>

        {{-- Main Container --}}
        <x-ui::main full-width drawer="main-drawer" class="flex-1 min-h-0">
            {{-- Sidebar --}}
            <x-slot:sidebar collapsible class="bg-base-200 border-r border-base-300 lg:bg-base-200">
                <div class="h-[calc(100vh-4rem)] overflow-y-auto custom-scrollbar p-2">
                    {{-- Sidebar Menu --}}
                    <x-mary-menu activate-by-route class="gap-1 p-0">
                        <x-ui::slot-render name="sidebar.menu" />
                    </x-mary-menu>
                </div>
            </x-slot:sidebar>

            {{-- Main Content --}}
            <div id="main-content" class="h-[calc(100vh-4rem)] overflow-y-auto p-6 lg:p-10 custom-scrollbar bg-base-300">
                @if($title)
                    <div class="mb-8">
                        <h1 class="text-3xl font-bold tracking-tight text-base-content">{{ $title }}</h1>
                    </div>
                @endif

                <div class="space-y-8 pb-20">
                    {{ $slot }}
                </div>
            </div>
        </x-ui::main>
    </div>
</x-ui::layouts.base>
