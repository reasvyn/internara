@props([
    'text' => null,
    'color' => 'primary',
    'round' => 'xl',
    'circle' => false,
])

{{--
    UI Wrapper: Button
    Wraps TallStackUI button (<x-ts-button>), enforcing design system tokens
    (canonical rounded-xl, standardized color palette, and accessible minimums).
--}}
<x-ts-button
    :text="$text"
    :color="$color"
    :round="$round"
    :circle="$circle"
    {{
        $attributes->class([
            'rounded-xl' => ! $circle && ! str_contains((string) $attributes->get('class'), 'rounded-'),
        ])
    }}
>
    {{ $slot }}
</x-ts-button>
