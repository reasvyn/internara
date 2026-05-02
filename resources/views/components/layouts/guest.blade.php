@props(['title' => null, 'header' => null, 'footer' => null])

<x-layouts.base :$title>
    <div class="min-h-screen flex flex-col bg-base-200">
        <!-- Guest Header (Simple Navbar) -->
        <header class="bg-base-100 shadow-sm border-b border-base-200 sticky top-0 z-50">
            <div class="container mx-auto px-4 md:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo & Brand -->
                    <div class="flex items-center gap-3">
                        <a href="/" class="flex items-center gap-3 group">
                            <img 
                                src="{{ App\Support\Branding::logo() }}" 
                                class="w-10 h-10 rounded-2xl object-cover shadow-sm transition-transform group-hover:scale-110 duration-300" 
                                alt="{{ App\Support\Branding::brandName() }}"
                            />
                            <span class="text-xl font-black tracking-tighter">
                                {{ App\Support\Branding::brandName() }}
                            </span>
                        </a>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <livewire:theme-switcher />
                        <livewire:language-switcher />
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main id="main-content" class="flex-1 flex flex-col">
            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="bg-base-100 border-t border-base-200 py-8">
            <div class="container mx-auto px-4 text-center">
                @isset($footer)
                    {{ $footer }}
                @else
                    <div class="flex flex-col items-center gap-2">
                        <livewire:layout.app-signature />
                        <p class="text-[10px] uppercase font-bold tracking-[0.2em] opacity-30">
                            {{ __('common.app_tagline', default: 'Professional Internship Management') }}
                        </p>
                    </div>
                @endisset
            </div>
        </footer>
    </div>
</x-layouts.base>
