
@php
    $brandName = setting('brand_name', setting('app_name', 'Internara'));
    $brandLogo = setting('brand_logo');

    if (empty($brandLogo)) {
        $brandLogo = asset('/brand/logo.png');
    }
@endphp

<a 
    {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}
    href="/" 
    wire:navigate 
    aria-label="{{ __('ui::common.go_to_home', ['name' => $brandName]) }}"
>
    <img 
        src="{{ $brandLogo }}" 
        alt="{{ __('ui::common.brand_logo_alt', ['name' => $brandName]) }}" 
        class="size-8 object-contain" 
    />
    <span class="text-lg font-bold">{{ $brandName }}</span>
</a>
