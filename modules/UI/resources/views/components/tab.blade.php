@props(['aos' => null])

<x-mary-tab {{ $attributes->merge(['class' => 'rounded-xl transition-all']) }}>
    <div class="py-4" :data-aos="$aos">
        {{ $slot }}
    </div>
</x-mary-tab>
