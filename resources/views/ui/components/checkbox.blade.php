@props([
    'label' => null,
    'hint' => null,
])

{{--
    UI Wrapper: Checkbox
    Wraps TallStackUI checkbox (<x-ts-checkbox>), ensuring rounded-xs corners.
--}}
<x-ts-checkbox
    :label="$label"
    :hint="$hint"
    {{
        $attributes->class([
            'rounded-xs' => ! str_contains((string) $attributes->get('class'), 'rounded-'),
        ])
    }}
/>
