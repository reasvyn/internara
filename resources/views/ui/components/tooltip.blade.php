@props([
    'text' => '',
])

{{--
    UI Wrapper: Tooltip
    Wraps TallStackUI tooltip (<x-ts-tooltip>), ensuring rounded-md corners.
--}}
<x-ts-tooltip :text="$text" {{
    $attributes->class([
        'rounded-md',
    ])
}}> {{ $slot }} </x-ts-tooltip>
