@props([
    'label' => null,
    'hint' => null,
    'placeholder' => null,
    'options' => [],
    'select' => null,
    'searchable' => false,
    'multiple' => false,
])

{{--
    UI Wrapper: Select
    Wraps TallStackUI styled select (<x-ts-select.styled>), ensuring non-sharp rounded corners.
--}}
<x-ts-select.styled
    :label="$label"
    :hint="$hint"
    :placeholder="$placeholder"
    :options="$options"
    :select="$select"
    :searchable="$searchable"
    :multiple="$multiple"
    {{
        $attributes->class([
            'rounded-lg' => ! str_contains((string) $attributes->get('class'), 'rounded-'),
        ])
    }}
/>
