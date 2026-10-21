@props([
    'text' => '',
])

{{--
    UI Wrapper: Tooltip
    Wraps TallStackUI tooltip (<x-ts-tooltip>), ensuring rounded-xl corners.
--}}
<x-ts-tooltip :text="$text" {{
    $attributes->class([
        'rounded-xl',
    ])
}}> {{ $slot }} </x-ts-tooltip>
