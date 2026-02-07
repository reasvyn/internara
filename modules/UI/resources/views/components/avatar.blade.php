@props([
    'image' => null,
    'title' => null,
    'subtitle' => null,
    'size' => 'w-12',
    'aos' => null,
])

<div 
    class="avatar {{ $image ? '' : 'placeholder' }}"
    role="img" 
    aria-label="{{ $title ?? __('ui::common.user_avatar') }}"
    :data-aos="$aos"
>
    <div {{ $attributes->merge(['class' => "rounded-2xl $size aspect-square"]) }}>
        @if($image)
            <img src="{{ $image }}" alt="{{ $title ?? '' }}" />
        @elseif($title)
            <div class="bg-base-300 text-base-content/60 flex items-center justify-center font-bold uppercase size-full">
                {{ substr($title, 0, 1) }}
            </div>
        @else
            <div class="bg-base-300 text-base-content/60 flex items-center justify-center size-full">
                <x-ui::icon name="tabler.user" class="size-1/2" />
            </div>
        @endif
    </div>
</div>
