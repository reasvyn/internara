@props([
    'title' => null,
    'recordTitle' => null,
    'context' => null,
])

<x-ui::layouts.base :$title body-class="bg-base-300">
    <div class="flex min-h-screen flex-col">
        {{-- 1. Full Width Navbar (Top) --}}
        <x-ui::navbar class="z-50 flex-none">
            <x-slot:hamburger>
                <label for="main-drawer" class="btn btn-ghost btn-sm btn-circle lg:hidden mr-2" aria-label="{{ __('ui::common.open_menu') }}">
                    <x-ui::icon name="tabler.menu-2" class="size-6" />
                </label>
            </x-slot:hamburger>
        </x-ui::navbar>

        {{-- 2. Layout Container (Bottom) --}}
        <div class="drawer lg:drawer-open flex-1">
            <input id="main-drawer" type="checkbox" class="drawer-toggle" />
            
            {{-- Main Content Area --}}
            <div class="drawer-content flex flex-col bg-base-300">
                <main id="main-content" class="flex-1">
                    <div class="py-8 px-4 sm:px-6 lg:px-10 max-w-7xl mx-auto">
                        {{-- Sitemap / Context Navigation --}}
                        @if($context)
                            <div class="mb-6 flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.15em] text-base-content/40">
                                <span>{{ setting('brand_name', 'Internara') }}</span>
                                <x-ui::icon name="tabler.chevron-right" class="size-2.5" />
                                <span>{{ __($context) }}</span>
                            </div>
                        @endif

                        {{-- Account Setup Required Banner --}}
                        @auth
                            @if(auth()->user()->requiresSetup())
                                <div class="mb-6">
                                    <x-ui::alert type="warning" icon="tabler.shield-exclamation" shadow>
                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <p class="font-semibold">{{ __('ui::common.setup_required.title') }}</p>
                                                <p class="text-sm opacity-80">{{ __('ui::common.setup_required.description') }}</p>
                                            </div>
                                            @if(\Illuminate\Support\Facades\Route::has('profile.index'))
                                                <a href="{{ route('profile.index') }}" wire:navigate
                                                   class="btn btn-warning btn-sm shrink-0">
                                                    <x-ui::icon name="tabler.lock" class="size-4" />
                                                    {{ __('ui::common.setup_required.action') }}
                                                </a>
                                            @endif
                                        </div>
                                    </x-ui::alert>
                                </div>
                            @elseif(auth()->user()->email && !auth()->user()->hasVerifiedEmail())
                                <div class="mb-6">
                                    <x-ui::alert type="info" icon="tabler.mail-check" shadow>
                                        <div class="flex items-center justify-between gap-4">
                                            <div class="min-w-0">
                                                <p class="font-semibold leading-snug">{{ __('ui::common.email_unverified.title') }}</p>
                                                <p class="mt-0.5 text-sm opacity-75">{{ __('ui::common.email_unverified.description') }}</p>
                                            </div>
                                            @if(\Illuminate\Support\Facades\Route::has('verification.notice'))
                                                <a href="{{ route('verification.notice') }}" wire:navigate
                                                   class="btn btn-info btn-sm shrink-0 self-center">
                                                    <x-ui::icon name="tabler.mail-forward" class="size-4" />
                                                    <span class="hidden sm:inline">{{ __('ui::common.email_unverified.action') }}</span>
                                                </a>
                                            @endif
                                        </div>
                                    </x-ui::alert>
                                </div>
                            @endif
                        @endauth

                        {{-- Optional Record Title --}}
                        @if($recordTitle)
                            <div class="mb-10">
                                <h1 class="text-4xl font-black tracking-tight text-base-content">{{ $recordTitle }}</h1>
                            </div>
                        @endif

                        {{-- Page Content --}}
                        <div class="space-y-8 pb-20">
                            {{ $slot }}
                        </div>
                    </div>
                </main>

                {{-- Dashboard Footer --}}
                <footer class="py-6 px-10 flex-none mt-auto">
                    <x-ui::app-credit />
                </footer>
            </div> 

            {{-- Sidebar Area --}}
            <div class="drawer-side z-40">
                <label for="main-drawer" aria-label="close sidebar" class="drawer-overlay"></label> 
                <x-ui::sidebar class="min-h-screen border-t border-base-300 lg:border-t-0 shadow-xl lg:shadow-none" />
            </div>
        </div>
    </div>
</x-ui::layouts.base>
