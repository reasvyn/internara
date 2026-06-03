@props([
    'title' => null,
    'header' => null,
    'footer' => null,
    'context' => null,
])

<x-core::layouts.base :$title>
    <div class="drawer lg:drawer-open min-h-screen">
        <input id="main-drawer" type="checkbox" class="drawer-toggle" />

        <x-core::layouts.sidebar />

        <div class="drawer-content flex flex-col bg-base-200/30">
            <x-core::layouts.header :$header />

            <main id="main-content" class="flex-1 flex flex-col">
                <div class="flex-1 container mx-auto max-w-7xl px-4 py-5 md:px-6 lg:px-8 flex flex-col">
                    @if($context)
                        <nav aria-label="Breadcrumb" class="mb-5 flex items-center gap-2 text-xs text-base-content/40">
                            <a href="{{ route('dashboard') }}" class="hover:text-primary transition-colors">
                                {{ brand('name') }}
                            </a>
                            <span class="text-base-content/20">/</span>
                            <span class="text-primary font-medium">
                                {{ __($context) }}
                            </span>
                        </nav>
                    @endif

                    <div class="flex-1">
                        {{ $slot }}
                    </div>
                </div>
            </main>

            <x-core::layouts.base.footer />
        </div>
    </div>
</x-core::layouts.base>
