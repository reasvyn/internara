@php
    $brandName = setting('brand_name', setting('app_name', 'Internara'));
    $brandLogo = setting('brand_logo');

    if (empty($brandLogo)) {
        $brandLogo = asset('/brand/logo.png');
    }
@endphp

<div id="preloader" class="fixed inset-0 z-[9999] flex items-center justify-center bg-base-100 transition-opacity duration-500">
    <div class="relative flex flex-col items-center gap-6">
        {{-- Logo and Spinner container --}}
        <div class="relative flex items-center justify-center">
            {{-- Spinner --}}
            <div class="absolute size-24 animate-spin rounded-full border-4 border-accent border-t-transparent opacity-20"></div>
            <div class="absolute size-24 animate-[spin_3s_linear_infinite] rounded-full border-t-4 border-accent"></div>
            
            {{-- Brand Logo --}}
            <div class="relative z-10 flex size-20 items-center justify-center overflow-hidden rounded-full bg-white p-2 shadow-inner">
                <img 
                    src="{{ $brandLogo }}" 
                    alt="{{ $brandName }}" 
                    class="size-full object-contain animate-pulse"
                />
            </div>
        </div>

        {{-- App Identity --}}
        <div class="flex flex-col items-center gap-1">
            <span class="text-sm font-black uppercase tracking-[0.3em] text-base-content">
                {{ $brandName }}
            </span>
            <div class="h-1 w-12 rounded-full bg-accent/20">
                <div class="h-full w-1/2 animate-[shimmer_2s_infinite_linear] rounded-full bg-accent"></div>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(200%); }
    }
</style>
