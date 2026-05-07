@props(['fullWidth' => false])

<footer class="bg-base-100/50 border-t border-base-content/5 py-6 sm:py-10 lg:py-12 mt-auto">
    <div @class([
        'mx-auto px-4 sm:px-6 lg:px-8',
        'container' => !$fullWidth,
    ])>
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 sm:gap-6">
            {{-- Brand --}}
            <div class="flex flex-col items-center sm:items-start gap-1 sm:gap-2">
                <div class="flex items-center gap-2">
                    <div class="size-5 sm:size-6 rounded-lg bg-primary flex items-center justify-center">
                        <img src="{{ brand('logo') }}" class="size-3 sm:size-4 object-contain brightness-0 invert" alt="{{ brand('name') }}" />
                    </div>
                    <span class="text-xs sm:text-sm font-black tracking-tight uppercase">{{ brand('name') }}</span>
                </div>
                <p class="text-[9px] sm:text-[10px] font-bold text-base-content/30 uppercase tracking-widest">
                    &copy; {{ date('Y') }} {{ brand('author.name') }}. All rights reserved.
                </p>
            </div>

            {{-- Metadata --}}
            <div class="flex flex-col sm:flex-row items-center gap-4 sm:gap-6 sm:items-center">
                <div class="flex flex-col items-center sm:items-end gap-1">
                    <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-[0.2em] text-base-content/20">Version</span>
                    <span class="text-[11px] sm:text-xs font-bold font-mono opacity-40">{{ App\Support\AppInfo::version() }}</span>
                </div>

                <div class="divider divider-horizontal sm:divider-horizontal mx-0 opacity-10 h-6 sm:h-8"></div>

                <div class="flex flex-col items-center sm:items-end gap-1">
                    <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-[0.2em] text-base-content/20">Environment</span>
                    <div class="flex items-center gap-1.5">
                        <div @class([
                            'size-1.5 rounded-full animate-pulse',
                            'bg-success' => app()->isProduction(),
                            'bg-warning' => !app()->isProduction(),
                        ])></div>
                        <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wide opacity-40">{{ app()->environment() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
