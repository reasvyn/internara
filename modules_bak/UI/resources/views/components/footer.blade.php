
@php
    $brandName = setting('brand_name', setting('app_name', 'Internara'));
    $appName = setting('app_name', 'Internara');
    $appVersion = \Illuminate\Support\Str::start(setting('app_version', '0.1.0'), 'v');
@endphp

<footer {{ $attributes->merge(['class' => 'mt-auto w-full p-6']) }}>
    {{ $slot }}

    <div class="container mx-auto">
        <x-ui::app-credit />
    </div>
</footer>
