@props([
    'text' => null,
    'color' => 'primary',
    'round' => false,
])

{{--
    UI Wrapper: Badge
    Wraps TallStackUI badge (<x-ts-badge>), ensuring non-sharp rounded corners.
--}}
<x-ts-badge
    :text="$text"
    :color="$color"
    :round="$round"
    {{
        $attributes->class([
            'rounded-md' => ! $round && ! str_contains((string) $attributes->get('class'), 'rounded-'),
        ])
    }}
>
    {{ $slot }}
</x-ts-badge>
