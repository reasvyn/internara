@props([
    'label' => null,
    'hint' => null,
    'icon' => null,
])

{{--
    UI Wrapper: Input
    Wraps TallStackUI input (<x-ts-input>), ensuring canonical rounded-lg corners.
--}}
<x-ts-input
    :label="$label"
    :hint="$hint"
    :icon="$icon"
    {{
        $attributes->class([
            'rounded-lg' => ! str_contains((string) $attributes->get('class'), 'rounded-'),
        ])
    }}
/>
