@props(['header' => null])

<x-ts-layout.header>
    <x-slot:left>
        <div class="flex items-center gap-2">
            <a wire:navigate href="{{ route('dashboard') }}" class="lg:hidden">
                <x-core::ui.logo size="4" />
            </a>
            @if ($header)
                <h1 class="text-lg font-semibold">{{ $header }}</h1>
            @endif
        </div>
    </x-slot:left>

    <x-slot:right>
        <x-core::ui.navbar-actions />
    </x-slot:right>
</x-ts-layout.header>
