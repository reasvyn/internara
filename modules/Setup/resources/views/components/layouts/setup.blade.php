@props(['title' => null])

<x-ui::layouts.base :$title>
    <x-ui::nav {{ $attributes }}>
        <x-slot:brand>
            @isset($hamburger)
                {{ $hamburger }}
            @endisset

            {{-- Brand --}}
            @if(slotExists('navbar.brand'))
                @slotRender('navbar.brand')
            @else
                <x-ui::brand />
            @endif

            <div class="badge badge-ghost ml-2 text-xs font-medium text-gray-500">
                {{ 'v' . setting('app_version') }}
            </div>
        </x-slot>

        {{-- Right side actions --}}
        <x-slot:actions class="flex items-center space-x-2">
            <x-ui::theme-toggle />
        </x-slot>
    </x-ui::nav>

    <main class="flex flex-1 flex-col items-center justify-center" data-aos="fade-up">
        <x-honeypot />
        {{ $slot }}
    </main>

    <footer class="border-base-200 mt-auto border-t">
        <x-ui::footer />
    </footer>
</x-ui::layouts.base>
