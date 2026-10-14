@props([
    'label' => null,
    'hint' => null,
    'rows' => 3,
])

{{--
    UI Wrapper: Textarea
    Wraps TallStackUI textarea (<x-ts-textarea>), ensuring canonical rounded-lg corners.
--}}
<x-ts-textarea
    :label="$label"
    :hint="$hint"
    :rows="$rows"
    {{
        $attributes->class([
            'rounded-lg' => ! str_contains((string) $attributes->get('class'), 'rounded-'),
        ])
    }}
/>
