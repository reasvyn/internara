@php
    $brandName = setting('brand_name', setting('app_name', 'Internara'));
@endphp

<footer {{ $attributes->merge(['class' => 'mt-auto w-full p-4']) }} data-aos="fade-in" data-aos-offset="0">
    {{ $slot }}

    <div class="container mx-auto">
        <p class="text-center text-sm font-medium">
            &copy; {{ now()->format('Y') }}
            <span class="font-bold">{{ $brandName }}</span>.
            @slotRender('footer.app-credit')
        </p>
    </div>
</footer>
