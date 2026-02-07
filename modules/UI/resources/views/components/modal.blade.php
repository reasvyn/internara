@props([
    'id' => \Illuminate\Support\Str::random(10),
    'title' => null,
    'subtitle' => null,
    'separator' => true,
    'aos' => 'zoom-in',
])

<x-mary-modal 
    {{ $attributes->merge(['class' => 'backdrop-blur-sm']) }}
    id="{{ $id }}"
    aria-labelledby="{{ $id }}-title"
>
    <div class="p-6 lg:p-8" :data-aos="$aos">
        @if($title)
            <div class="mb-6">
                <h3 id="{{ $id }}-title" class="text-2xl font-bold text-base-content">{{ $title }}</h3>
                @if($subtitle)
                    <p class="text-base-content/60 mt-1 text-sm">{{ $subtitle }}</p>
                @endif
                
                @if($separator)
                    <div class="divider my-4 opacity-50"></div>
                @endif
            </div>
        @endif

        <div class="space-y-6">
            {{ $slot }}
        </div>
        
        @isset($actions)
            <div class="modal-action mt-10 gap-2">
                {{ $actions }}
            </div>
        @endisset
    </div>
</x-mary-modal>