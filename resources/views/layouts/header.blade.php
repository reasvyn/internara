@props(['header' => null])

<header class="bg-base-100/80 backdrop-blur-sm border-b border-base-content/10 sticky top-0 z-50">
    <div class="container mx-auto max-w-7xl px-4 md:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center gap-3 lg:hidden">
                <label for="main-drawer" class="btn btn-ghost btn-sm btn-circle">
                    <x-mary-icon name="o-bars-3" class="size-5" />
                </label>
                <a wire:navigate href="{{ route('dashboard') }}">
                    <x-shared::ui.logo size="4" />
                </a>
            </div>

            <div class="hidden lg:flex items-center">
                @if($header)
                    <h1 class="text-lg font-semibold text-base-content">{{ $header }}</h1>
                @endif
            </div>

            <x-shared::ui.navbar-actions />
        </div>
    </div>
</header>
