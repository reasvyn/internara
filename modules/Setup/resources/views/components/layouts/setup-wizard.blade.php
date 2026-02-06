@props([
    'header' => null,
    'content' => null,
])

<div class="container flex h-full w-full flex-1 flex-col gap-x-8 gap-y-20 py-20 lg:flex-row">
    <div class="order-1 flex h-full w-full flex-1 flex-col lg:order-2 lg:pt-24" data-aos="fade-left">
        {{ $header }}
    </div>

    @isset($content)
        <div class="order-2 flex h-full w-full flex-1 flex-col lg:order-1" data-aos="fade-right">
            {{ $content }}
        </div>
    @endisset
</div>
