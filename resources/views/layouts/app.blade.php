@props([
    'title' => null,
    'header' => null,
    'footer' => null,
    'context' => null,
])

<x-layouts::base :$title>
    {{-- Layout Container --}}
    <div class="drawer lg:drawer-open min-h-screen">
        <input id="main-drawer" type="checkbox" class="drawer-toggle" />

        <!-- Sidebar / Navigation Drawer -->
        <x-layouts.sidebar />

        <!-- Main Workspace -->
        <div class="drawer-content flex flex-col bg-base-200/50 relative overflow-x-hidden">
            
            {{-- Professional Header Component --}}
            <x-layouts.header :$header />

            <!-- Content Area -->
            <main id="main-content" class="flex-1 flex flex-col relative">
                {{-- Decorative Background Gradients --}}
                <div class="absolute inset-0 pointer-events-none overflow-hidden">
                    <div class="absolute -top-[10%] -right-[10%] w-[40%] h-[40%] bg-primary/5 blur-[120px] rounded-full opacity-60"></div>
                    <div class="absolute top-[20%] -left-[10%] w-[30%] h-[30%] bg-secondary/5 blur-[100px] rounded-full opacity-40"></div>
                </div>

                <div class="flex-1 container mx-auto max-w-7xl px-4 py-6 md:px-8 lg:px-10 relative z-10 flex flex-col">
                    
                    {{-- Breadcrumbs / Context Bar --}}
                    @if($context)
                        <nav aria-label="Breadcrumb" class="mb-6 flex items-center gap-2 overflow-x-auto whitespace-nowrap scrollbar-hide">
                            <a href="{{ route('dashboard') }}" class="text-[10px] font-black uppercase tracking-widest text-base-content/40 hover:text-primary transition-colors">
                                {{ brand('name') }}
                            </a>
                            <x-mary-icon name="o-chevron-right" class="size-3 text-base-content/20" />
                            <span class="text-[10px] font-black uppercase tracking-widest text-primary">
                                {{ __($context) }}
                            </span>
                        </nav>
                    @endif

                    {{-- Dynamic Slot Content with refined spacing and animation --}}
                    <div class="flex-1 animate-in fade-in slide-in-from-bottom-2 duration-500">
                        {{ $slot }}
                    </div>
                </div>
            </main>

            {{-- Professional Footer Component --}}
            <x-layouts::base.footer />
        </div>
    </div>
</x-layouts::base>
