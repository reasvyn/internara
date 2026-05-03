@props(['title' => null, 'header' => null, 'footer' => null])

<x-layouts.base :$title>
    <div class="min-h-screen flex flex-col bg-base-300 relative overflow-hidden">
        {{-- Decorative Background Elements (Modern UI Pattern) --}}
        <div class="absolute -top-[10%] -left-[10%] size-[40%] rounded-full bg-primary/5 blur-[120px] animate-pulse"></div>
        <div class="absolute -bottom-[10%] -right-[10%] size-[40%] rounded-full bg-secondary/5 blur-[120px] animate-pulse" style="animation-delay: 2s"></div>

        <!-- Guest Header (Modern Glassmorphism) -->
        <header class="bg-base-100/60 backdrop-blur-xl border-b border-base-content/5 sticky top-0 z-50">
            <div class="container mx-auto px-6 lg:px-12">
                <div class="flex items-center justify-between h-20">
                    <!-- Logo & Brand -->
                    <div class="flex items-center gap-4">
                        <a wire:navigate href="/" class="flex items-center gap-4 group">
                            <div class="size-11 rounded-2xl bg-primary flex items-center justify-center shadow-lg shadow-primary/20 transition-transform group-hover:scale-110 duration-500">
                                <img 
                                    src="{{ brand('logo') }}" 
                                    class="size-7 object-contain brightness-0 invert" 
                                    alt="{{ brand('name') }}"
                                />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xl font-black tracking-tighter leading-none">
                                    {{ brand('name') }}
                                </span>
                                <span class="text-[9px] uppercase tracking-[0.3em] font-black opacity-30 mt-1">Management System</span>
                            </div>
                        </a>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-3">
                        <div class="hidden md:flex items-center gap-1 mr-4 bg-base-200/50 p-1 rounded-xl border border-base-content/5">
                            <livewire:theme-switcher />
                        </div>
                        <livewire:language-switcher />
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main id="main-content" class="flex-1 flex flex-col relative z-10">
            {{ $slot }}
        </main>

        <!-- Modern Footer -->
        <footer class="bg-base-100/40 backdrop-blur-md border-t border-base-content/5 py-12 mt-auto">
            <div class="container mx-auto px-6 text-center">
                @isset($footer)
                    {{ $footer }}
                @else
                    <div class="flex flex-col items-center gap-6">
                        <div class="flex items-center gap-8 opacity-20 hover:opacity-100 transition-opacity duration-700 grayscale hover:grayscale-0">
                            {{-- Placeholder for Partner Logos --}}
                            <div class="text-[10px] font-black uppercase tracking-widest">Industry Ready</div>
                            <div class="text-[10px] font-black uppercase tracking-widest">Enterprise Secured</div>
                            <div class="text-[10px] font-black uppercase tracking-widest">Open Source</div>
                        </div>

                        <div class="flex flex-col items-center gap-2">
                            <livewire:layout.app-signature />
                            <p class="text-[9px] uppercase font-black tracking-[0.4em] opacity-20">
                                {{ trans('common.app_tagline') ?: 'Professional Internship Management' }}
                            </p>
                        </div>
                    </div>
                @endisset
            </div>
        </footer>
    </div>
</x-layouts.base>
