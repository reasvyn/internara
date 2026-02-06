@props(['aos' => null])

<x-mary-card 
    {{ $attributes->merge(['class' => 'bg-base-100 border-base-200 rounded-2xl border shadow-sm transition-shadow hover:shadow-md']) }}
    :data-aos="$aos"
>
    {{ $slot }}
</x-mary-card>