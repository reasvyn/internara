@props(['title' => null])

<x-layout:base :$title>
    <div class="min-h-screen flex flex-col bg-base-100 relative overflow-hidden">
        {{-- Background Decorations --}}
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-primary via-secondary to-accent"></div>
        <div class="absolute -top-24 -left-24 size-96 rounded-full bg-primary/5 blur-3xl opacity-50 pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 size-96 rounded-full bg-secondary/5 blur-3xl opacity-50 pointer-events-none"></div>

        <!-- Professional Setup Header -->
        <header class="bg-base-100/70 backdrop-blur-xl border-b border-base-content/5 sticky top-0 z-50">
            <div class="container mx-auto px-4 sm:px-6 lg:px-12 max-w-7xl">
                <div class="flex items-center justify-between h-20">
                    {{-- UI Brand --}}
                    <x-ui:brand :invert="false" />

                    {{-- Actions Bar --}}
                    <div class="flex items-center gap-2 sm:gap-4">
                        <div class="flex items-center gap-1 p-1 bg-base-200/50 rounded-2xl border border-base-content/5">
                            <x-ui:theme-switcher class="px-2" />
                            <div class="divider divider-horizontal mx-0 opacity-10 h-6"></div>
                            <x-ui:lang-switcher class="px-2" />
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main id="main-content" class="flex-1 flex flex-col py-8 sm:py-12 lg:py-16 relative z-10">
            <div class="container mx-auto px-4 sm:px-6 lg:px-12 max-w-5xl">
                {{-- Content with animation --}}
                <div class="animate-in fade-in slide-in-from-bottom-4 duration-700">
                    {{ $slot }}
                </div>
            </div>
        </main>

        <!-- Professional Setup Footer -->
        <footer class="bg-base-200/30 border-t border-base-content/5 py-8 mt-auto">
            <div class="container mx-auto px-4 sm:px-6 lg:px-12 max-w-7xl">
                <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="flex flex-col items-center md:items-start gap-2">
                        <div class="flex items-center gap-2 opacity-30 grayscale">
                            <img src="{{ brand('logo') }}" class="size-4 object-contain" alt="{{ brand('name') }}" />
                            <span class="text-[10px] font-black uppercase tracking-widest">{{ brand('name') }}</span>
                        </div>
                        <p class="text-[10px] font-bold text-base-content/30 uppercase tracking-[0.2em]">
                            &copy; {{ date('Y') }} {{ brand('author.name') }}. {{ __('All rights reserved.') }}
                        </p>
                    </div>

                    <div class="flex items-center gap-8">
                        <div class="flex flex-col items-center md:items-end gap-1">
                            <span class="text-[8px] font-black uppercase tracking-[0.2em] text-base-content/20">Version</span>
                            <span class="text-xs font-bold font-mono opacity-30">{{ App\Support\AppInfo::version() }}</span>
                        </div>
                        <div class="flex flex-col items-center md:items-end gap-1">
                            <span class="text-[8px] font-black uppercase tracking-[0.2em] text-base-content/20">Status</span>
                            <div class="flex items-center gap-1.5">
                                <div class="size-1.5 rounded-full bg-primary animate-pulse shadow-sm shadow-primary/50"></div>
                                <span class="text-[10px] font-black uppercase tracking-widest opacity-30">Provisioning</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</x-layout:base>
