@props([
    'label' => null,
    'hint' => null,
])

{{--
    UI Wrapper: Checkbox
    Wraps TallStackUI checkbox (<x-ts-checkbox>), ensuring rounded-md corners.
--}}
<x-ts-checkbox
    :label="$label"
    :hint="$hint"
    {{
        $attributes->class([
            'rounded-md' => ! str_contains((string) $attributes->get('class'), 'rounded-'),
        ])
    }}
/>
