@props([
    'text' => null,
    'color' => 'primary',
    'round' => false,
    'circle' => false,
])

{{--
    UI Wrapper: Button
    Wraps TallStackUI button (<x-ts-button>), enforcing design system tokens
    (canonical rounded-lg, standardized color palette, and accessible minimums).
--}}
<x-ts-button
    :text="$text"
    :color="$color"
    :round="$round"
    :circle="$circle"
    {{
        $attributes->class([
            'rounded-lg' => ! $round && ! $circle && ! str_contains((string) $attributes->get('class'), 'rounded-'),
        ])
    }}
>
    {{ $slot }}
</x-ts-button>
