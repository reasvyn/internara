@props(['aos' => 'fade-up'])

@php
    $brandName = setting('brand_name', setting('app_name', 'Internara'));
    $appName = setting('app_name', 'Internara');
    $appVersion = \Illuminate\Support\Str::start(setting('app_version', '0.1.0'), 'v');
@endphp

<footer {{ $attributes->merge(['class' => 'mt-auto w-full p-6']) }} data-aos="{{ $aos }}" data-aos-offset="0" data-aos-duration="1000">
    {{ $slot }}

    <div class="container mx-auto">
        <div class="flex flex-wrap items-center justify-center gap-x-2 gap-y-2 text-center text-xs font-medium">
            <p class="whitespace-nowrap text-base-content/60">
                &copy; {{ now()->format('Y') }}
                <span class="font-bold">{{ $brandName }}</span>
            </p>
            
            <span class="hidden md:block size-1 rounded-full bg-base-content/10"></span>

            <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em] opacity-30">
                @if($brandName !== $appName)
                    <span class="whitespace-nowrap">{{ $appName }}</span>
                    <span class="size-1 rounded-full bg-base-content/40"></span>
                @endif
                <span class="whitespace-nowrap">{{ $appVersion }}</span>
            </div>

            <span class="hidden md:block size-1 rounded-full bg-base-content/10"></span>

            <div class="whitespace-nowrap opacity-60">
                @slotRender('footer.app-credit')
            </div>
        </div>
    </div>
</footer>
