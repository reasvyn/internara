@props([
    'title' => null,
    'blur' => true,
    'separator' => true,
    'size' => '2xl',
    'header' => null,
    'footer' => null,
])

{{--
    UI Wrapper: Modal
    Wraps TallStackUI modal (<x-ts-modal>), enforcing rounded-2xl container and backdrop blur.
--}}
<x-ts-modal
    :title="$title"
    :blur="$blur"
    :separator="$separator"
    :size="$size"
    {{
        $attributes->class([
            'rounded-2xl',
        ])
    }}
>
    @isset($header)
        <x-slot:header>{{ $header }}</x-slot:header>
    @endisset

    {{ $slot }}

    @isset($footer)
        <x-slot:footer>{{ $footer }}</x-slot:footer>
    @endisset
</x-ts-modal>
