@props(['fullWidth' => false])

<footer class="bg-base-100 border-t border-base-content/5 py-8 sm:py-12 relative overflow-hidden mt-auto">
    {{-- Decorative element --}}
    <div class="absolute bottom-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-primary/20 to-transparent"></div>
    
    <div @class([
        'mx-auto px-4 sm:px-8 lg:px-10',
        'container max-w-7xl' => !$fullWidth,
    ])>
        <div class="grid grid-cols-1 md:grid-cols-12 gap-10">
            
            {{-- Brand Section --}}
            <div class="md:col-span-4 flex flex-col items-start gap-4">
                <a wire:navigate href="{{ route('dashboard') }}" class="flex items-center gap-2 group">
                    <div class="size-8 rounded-lg bg-primary flex items-center justify-center shadow-lg shadow-primary/20 transition-transform group-hover:scale-110">
                        <img src="{{ brand('logo') }}" class="size-4 object-contain brightness-0 invert" alt="{{ brand('name') }}" />
                    </div>
                    <span class="text-sm font-black tracking-tight uppercase">{{ brand('name') }}</span>
                </a>
                <p class="text-xs font-medium text-base-content/50 leading-relaxed max-w-xs">
                    {{ brand('description') ?? 'A professional internship management system designed for excellence and efficiency.' }}
                </p>
                <div class="flex items-center gap-3 mt-2">
                    @foreach(['o-globe-alt', 'o-chat-bubble-left-right', 'o-envelope'] as $icon)
                        <button class="size-8 rounded-lg bg-base-200 flex items-center justify-center text-base-content/40 hover:bg-primary hover:text-primary-content transition-all">
                            <x-mary-icon :name="$icon" class="size-4" />
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Links Sections (Placeholders for professional look) --}}
            <div class="md:col-span-2">
                <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-base-content/30 mb-4 italic">Platform</h4>
                <ul class="flex flex-col gap-2">
                    <li><a href="#" class="text-xs font-bold text-base-content/60 hover:text-primary transition-colors">Dashboard</a></li>
                    <li><a href="#" class="text-xs font-bold text-base-content/60 hover:text-primary transition-colors">Documentation</a></li>
                    <li><a href="#" class="text-xs font-bold text-base-content/60 hover:text-primary transition-colors">Support</a></li>
                </ul>
            </div>

            <div class="md:col-span-2">
                <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-base-content/30 mb-4 italic">System</h4>
                <ul class="flex flex-col gap-2">
                    <li><a href="#" class="text-xs font-bold text-base-content/60 hover:text-primary transition-colors">Status</a></li>
                    <li><a href="#" class="text-xs font-bold text-base-content/60 hover:text-primary transition-colors">Security</a></li>
                    <li><a href="#" class="text-xs font-bold text-base-content/60 hover:text-primary transition-colors">Privacy</a></li>
                </ul>
            </div>

            {{-- Metadata Section --}}
            <div class="md:col-span-4 flex flex-col md:items-end gap-6">
                <div class="flex items-center gap-6">
                    <div class="flex flex-col items-start md:items-end gap-1">
                        <span class="text-[9px] font-black uppercase tracking-[0.2em] text-base-content/20">Version</span>
                        <span class="text-xs font-bold font-mono opacity-40">{{ App\Support\AppInfo::version() }}</span>
                    </div>

                    <div class="divider divider-horizontal mx-0 opacity-5 h-8"></div>

                    <div class="flex flex-col items-start md:items-end gap-1">
                        <span class="text-[9px] font-black uppercase tracking-[0.2em] text-base-content/20">Environment</span>
                        <div class="flex items-center gap-1.5">
                            <div @class([
                                'size-1.5 rounded-full shadow-sm',
                                'bg-success shadow-success/20' => app()->isProduction(),
                                'bg-warning shadow-warning/20' => !app()->isProduction(),
                            ])></div>
                            <span class="text-xs font-bold uppercase tracking-wide opacity-40">{{ app()->environment() }}</span>
                        </div>
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-base-200/50 border border-base-content/5 w-full max-w-xs md:text-right">
                    <p class="text-[10px] font-black text-base-content/40 uppercase tracking-widest leading-loose">
                        &copy; {{ date('Y') }} {{ brand('author.name') }}.<br/>
                        All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>
