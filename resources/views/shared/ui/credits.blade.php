@props(['full' => true])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-6']) }}>
    <div class="flex items-center gap-8 opacity-20 hover:opacity-100 transition-opacity duration-700 grayscale hover:grayscale-0">
        <div class="text-[10px] font-black uppercase tracking-widest">Industry Ready</div>
        <div class="text-[10px] font-black uppercase tracking-widest">Enterprise Secured</div>
        <div class="text-[10px] font-black uppercase tracking-widest">Open Source</div>
    </div>

    <div class="flex flex-col items-center gap-2">
        <livewire:settings.app-signature />
        @if($full)
            <p class="text-[9px] uppercase font-black tracking-[0.4em] opacity-20">
                {{ brand('tagline') ?: 'Professional Internship Management' }}
            </p>
        @endif
    </div>
</div>
