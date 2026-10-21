@props([
    'label' => null,
    'hint' => null,
    'icon' => null,
])

{{--
    UI Wrapper: Input
    Wraps TallStackUI input (<x-ts-input>), ensuring canonical rounded-xl corners.
--}}
<x-ts-input
    :label="$label"
    :hint="$hint"
    :icon="$icon"
    {{
        $attributes->class([
            'rounded-xl' => ! str_contains((string) $attributes->get('class'), 'rounded-'),
        ])
    }}
/>
