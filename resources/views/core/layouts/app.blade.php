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

        <div class="drawer-content bg-base-200/30 flex flex-col">
            <x-core::layouts.header :$header />

            <main id="main-content" class="flex flex-1 flex-col">
                <div class="container mx-auto flex max-w-7xl flex-1 flex-col px-4 py-5 md:px-6 lg:px-8">
                    @if ($context)
                        <nav aria-label="Breadcrumb" class="text-base-content/60 mb-5 flex items-center gap-2 text-xs">
                            <a href="{{ route('dashboard') }}" class="hover:text-primary transition-colors">
                                {{ brand('name') }}
                            </a>
                            <span class="text-base-content/60">/</span>
                            <span class="text-primary font-medium"> {{ __($context) }} </span>
                        </nav>
                    @endif

                    <div class="flex-1">{{ $slot }}</div>
                </div>
            </main>

            <x-core::layouts.base.footer />
        </div>
    </div>

    <x-mary-spotlight />
</x-core::layouts.base>
