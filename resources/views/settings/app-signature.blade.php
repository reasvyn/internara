<div class="text-center py-6 border-t border-base-content/5 mt-8">
    <div class="flex flex-col sm:flex-row items-center justify-center gap-2 sm:gap-4">
        <div class="flex items-center gap-2">
            <span class="text-[10px] font-black uppercase tracking-widest text-base-content/30">&copy; {{ date('Y') }}</span>
            <span class="text-xs font-black tracking-tight text-base-content/60">{{ $app_name }}</span>
        </div>
        
        <div class="hidden sm:block size-1 rounded-full bg-base-content/10"></div>
        
        <x-mary-badge :value="$app_version" class="badge-neutral font-black text-[9px] uppercase px-2 py-0 h-4 border-none opacity-50" />
        
        @if (!empty($author))
            <div class="hidden sm:block size-1 rounded-full bg-base-content/10"></div>
            <div class="flex items-center gap-1.5">
                <span class="text-[10px] font-black uppercase tracking-widest text-base-content/20">Handcrafted by</span>
                @if (!empty($author['github']))
                    <a href="{{ $author['github'] }}" target="_blank" rel="noopener noreferrer" class="text-xs font-black tracking-tight text-primary hover:underline decoration-2 underline-offset-4 transition-all">{{ $author['name'] ?? $author }}</a>
                @else
                    <span class="text-xs font-black tracking-tight text-base-content/60">{{ $author['name'] ?? $author }}</span>
                @endif
            </div>
        @endif
        
        <div class="hidden sm:block size-1 rounded-full bg-base-content/10"></div>
        
        <span class="text-[10px] font-black uppercase tracking-widest text-base-content/20">{{ $app_license }} License</span>
    </div>
</div>
