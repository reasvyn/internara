@props(['aos' => null])

<a class="flex items-center gap-2" href="/" wire:navigate :data-aos="$aos">
    <span class="text-lg font-bold">{{ setting('brand_name', setting('app_name')) }}</span>
</a>
