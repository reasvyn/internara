@props(['header' => null])

<header class="bg-base-100/80 border-base-content/10 sticky top-0 z-50 border-b backdrop-blur-sm">
    <div class="container mx-auto max-w-7xl px-4 md:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-3 lg:hidden">
                <label
                    for="main-drawer"
                    aria-label="{{ __('common.actions.menu') }}"
                    class="btn btn-ghost btn-sm btn-circle"
                >
                    <x-mary-icon name="o-bars-3" class="size-5" />
                </label>
                <a wire:navigate href="{{ route('dashboard') }}">
                    <x-core::ui.logo size="4" />
                </a>
            </div>

            <div class="hidden items-center lg:flex">
                @if ($header)
                    <h1 class="text-base-content text-lg font-semibold">{{ $header }}</h1>
                @endif
            </div>

            <x-core::ui.navbar-actions />
        </div>
    </div>
</header>
