@props([
    'title' => null,
    'subtitle' => null,
    'middle' => null, 
    'actions' => null,
    'separator' => false,
    'aos' => 'fade-down',
])

<x-mary-header 
    {{ $attributes->merge(['class' => 'mb-8']) }}
    :title="$title"
    :subtitle="$subtitle"
    :separator="$separator"
    :data-aos="$aos"
>
    @if($middle)
        <x-slot:middle>
            {{ $middle }}
        </x-slot:middle>
    @endif

    @if($actions)
        <x-slot:actions>
            <div class="flex items-center gap-3">
                {{ $actions }}
            </div>
        </x-slot:actions>
    @endif
</x-mary-header>
