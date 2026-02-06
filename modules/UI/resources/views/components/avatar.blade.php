@props([
    'image' => null,
    'title' => null,
    'subtitle' => null,
    'size' => 'w-12',
    'aos' => null,
])

<x-mary-avatar 
    {{ $attributes->merge(['class' => 'rounded-2xl']) }}
    :image="$image"
    :title="$title"
    :subtitle="$subtitle"
    :class="$size"
    :data-aos="$aos"
>
    @if(!$image && $title)
        <x-slot:placeholder>
            <div class="bg-base-300 text-base-content/60 flex items-center justify-center font-bold uppercase rounded-2xl {{ $size }} aspect-square">
                {{ substr($title, 0, 1) }}
            </div>
        </x-slot:placeholder>
    @endif
</x-mary-avatar>
