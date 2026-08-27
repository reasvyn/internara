@props(['full' => true])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-6']) }}>
    <div class="flex items-center gap-8 opacity-60 grayscale transition-opacity duration-700 hover:opacity-100 hover:grayscale-0">
        <div class="text-[10px] font-black tracking-widest uppercase">{{ __('common.industry_ready') }}</div>
        <div class="text-[10px] font-black tracking-widest uppercase">{{ __('common.enterprise_secured') }}</div>
        <div class="text-[10px] font-black tracking-widest uppercase">{{ __('common.open_source') }}</div>
    </div>

    <div class="flex flex-col items-center gap-2">
        <x-ui::components.app-signature />
        @if ($full)
            <p class="text-[9px] font-black tracking-[0.4em] uppercase opacity-60">
                {{ brand('tagline') ?: __('common.professional_internship_management') }}
            </p>
        @endif
    </div>
</div>
