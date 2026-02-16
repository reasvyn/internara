@props([
    'actions' => null,
    'separator' => false,
    'aos' => null,
])

<x-mary-form 
    {{ $attributes->merge(['class' => 'space-y-8']) }}
    data-aos="{{ $aos }}"
>
    <div class="space-y-6">
        {{ $slot }}
    </div>

    @if($separator)
        <div class="divider opacity-50"></div>
    @endif

    @if($actions)
        <x-slot:actions>
            <div class="flex items-center gap-4">
                {{ $actions }}
            </div>
        </x-slot>
    @endif
</x-mary-form>