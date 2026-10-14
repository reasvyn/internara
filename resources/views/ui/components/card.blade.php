@props([
    'header' => null,
    'footer' => null,
    'round' => 'xl',
])

{{--
    UI Wrapper: Card
    Wraps TallStackUI card (<x-ts-card>), enforcing the canonical Internara card spec:
    rounded-xl, border-base-content/10, background-base-100, and non-sharp corners.
--}}
<x-ts-card
    :round="$round"
    {{
        $attributes->class([
            'rounded-xl border border-base-content/10 bg-base-100 shadow-2xs',
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
</x-ts-card>
