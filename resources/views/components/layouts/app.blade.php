@props([
    'title' => null, 
    'header' => null, 
    'footer' => null,
    'context' => null,
])

<x-layouts.base :$title>
    <div class="drawer lg:drawer-open min-h-screen">
        <input id="main-drawer" type="checkbox" class="drawer-toggle" />

        <!-- Sidebar (Drawer Side) -->
        <x-layouts.sidebar />

        <!-- Main Content (Drawer Content) -->
        <div class="drawer-content flex flex-col bg-base-200">
            <!-- Top Navbar -->
            <x-layouts.header :$header />

            <!-- Page Content -->
            <main id="main-content" class="flex-1">
                <div class="container mx-auto px-4 py-6 md:px-6 lg:px-8 max-w-7xl">
                    {{-- Sitemap / Context Navigation --}}
                    @if($context)
                        <div class="mb-6 flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.15em] text-base-content/40">
                            <span>{{ App\Support\Branding::brandName() }}</span>
                            <x-mary-icon name="o-chevron-right" class="size-2.5" />
                            <span>{{ __($context) }}</span>
                        </div>
                    @endif

                    {{-- Automated Status Banners --}}
                    @auth
                        @if(auth()->user()->requiresSetup())
                            <div class="mb-6">
                                <x-mary-alert icon="o-shield-exclamation" class="alert-warning alert-enterprise">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between w-full">
                                        <div>
                                            <p class="font-black text-sm uppercase tracking-tight">{{ __('common.setup_required.title') }}</p>
                                            <p class="text-xs font-medium opacity-80 mt-0.5">{{ __('common.setup_required.description') }}</p>
                                        </div>
                                        <a href="{{ route('profile') }}" wire:navigate
                                           class="btn btn-warning btn-sm shrink-0 rounded-xl font-black uppercase tracking-widest text-[10px] px-6">
                                            <x-mary-icon name="o-lock-closed" class="size-4" />
                                            {{ __('common.setup_required.action') }}
                                        </a>
                                    </div>
                                </x-mary-alert>
                            </div>
                        @elseif(auth()->user()->email && !auth()->user()->hasVerifiedEmail())
                            <div class="mb-6">
                                <x-mary-alert icon="o-envelope" class="alert-info alert-enterprise">
                                    <div class="flex items-center justify-between gap-4 w-full">
                                        <div class="min-w-0">
                                            <p class="font-black text-sm uppercase tracking-tight">{{ __('common.email_unverified.title') }}</p>
                                            <p class="mt-0.5 text-xs font-medium opacity-80">{{ __('common.email_unverified.description') }}</p>
                                        </div>
                                        @if(\Illuminate\Support\Facades\Route::has('verification.notice'))
                                            <a href="{{ route('verification.notice') }}" wire:navigate
                                               class="btn btn-info btn-sm shrink-0 self-center rounded-xl font-black uppercase tracking-widest text-[10px] px-6">
                                                <x-mary-icon name="o-paper-airplane" class="size-4" />
                                                <span class="hidden sm:inline">{{ __('common.email_unverified.action') }}</span>
                                            </a>
                                        @endif
                                    </div>
                                </x-mary-alert>
                            </div>
                        @endif
                    @endauth

                    <!-- Mobile title (shown only on mobile) -->
                    @isset($header)
                        <div class="lg:hidden mb-6">
                            <h1 class="text-2xl font-bold">{{ $header }}</h1>
                            @isset($title)
                                <p class="text-sm opacity-60">{{ $title }}</p>
                            @endisset
                        </div>
                    @endisset

                    {{ $slot }}
                </div>
            </main>

            <!-- Footer -->
            @isset($footer)
                <footer class="mt-auto">
                    <div class="container mx-auto px-4 text-center md:px-6 lg:px-8 py-4">
                        {{ $footer }}
                    </div>
                </footer>
            @else
                <footer class="mt-auto">
                    <div class="container mx-auto px-4 text-center md:px-6 lg:px-8 py-4">
                        <livewire:layout.app-signature />
                    </div>
                </footer>
            @endisset
        </div>
    </div>
</x-layouts.base>
