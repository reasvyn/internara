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
        <div class="drawer-content flex flex-col bg-base-200/30 relative">
            {{-- Subtle Background Decoration --}}
            <div class="absolute top-0 right-0 -mt-20 -mr-20 size-96 rounded-full bg-primary/5 blur-3xl opacity-50 pointer-events-none"></div>

            <!-- Top Navbar -->
            <x-layouts.header :$header />

            <!-- Page Content -->
            <main id="main-content" class="flex-1 relative z-10">
                <div class="container mx-auto px-4 py-8 md:px-8 lg:px-12 max-w-7xl animate-in fade-in slide-in-from-bottom-4 duration-700">
                    {{-- Sitemap / Context Navigation --}}
                    @if($context)
                        <div class="mb-8 flex items-center gap-3 text-[10px] font-black uppercase tracking-[0.2em] text-base-content/40">
                            <span class="text-base-content/60">{{ brand('name') }}</span>
                            <x-mary-icon name="o-chevron-right" class="size-3" />
                            <span>{{ __($context) }}</span>
                        </div>
                    @endif

                    {{-- Automated Status Banners --}}
                    @auth
                        @if(auth()->user()->requiresSetup())
                            <div class="mb-8">
                                <x-mary-alert icon="o-shield-exclamation" class="alert-warning !bg-warning/10 !border-warning/20 !text-warning-content rounded-3xl p-6 shadow-xl shadow-warning/5">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between w-full">
                                        <div>
                                            <p class="font-black text-sm uppercase tracking-tight">{{ __('common.setup_required.title') }}</p>
                                            <p class="text-xs font-medium opacity-80 mt-1">{{ __('common.setup_required.description') }}</p>
                                        </div>
                                        <a href="{{ route('profile') }}" wire:navigate
                                           class="btn btn-warning rounded-[2rem] font-black uppercase tracking-widest text-[10px] px-8 h-12 shadow-lg shadow-warning/20 shrink-0">
                                            <x-mary-icon name="o-lock-closed" class="size-4" />
                                            {{ __('common.setup_required.action') }}
                                        </a>
                                    </div>
                                </x-mary-alert>
                            </div>
                        @elseif(auth()->user()->email && !auth()->user()->hasVerifiedEmail())
                            <div class="mb-8">
                                <x-mary-alert icon="o-envelope" class="alert-info !bg-info/10 !border-info/20 !text-info-content rounded-3xl p-6 shadow-xl shadow-info/5">
                                    <div class="flex items-center justify-between gap-6 w-full">
                                        <div class="min-w-0">
                                            <p class="font-black text-sm uppercase tracking-tight">{{ __('common.email_unverified.title') }}</p>
                                            <p class="mt-1 text-xs font-medium opacity-80">{{ __('common.email_unverified.description') }}</p>
                                        </div>
                                        @if(\Illuminate\Support\Facades\Route::has('verification.notice'))
                                            <a href="{{ route('verification.notice') }}" wire:navigate
                                               class="btn btn-info rounded-[2rem] font-black uppercase tracking-widest text-[10px] px-8 h-12 shadow-lg shadow-info/20 shrink-0">
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
                        <div class="lg:hidden mb-8 text-center sm:text-left">
                            <h1 class="text-3xl font-black tracking-tighter">{{ $header }}</h1>
                            @isset($title)
                                <p class="text-xs font-black uppercase tracking-[0.2em] opacity-40 mt-2">{{ $title }}</p>
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
