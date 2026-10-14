@props([
    'label' => null,
    'hint' => null,
])

{{--
    UI Wrapper: Toggle / Switch
    Wraps TallStackUI toggle (<x-ts-toggle>).
--}}
<x-ts-toggle :label="$label" :hint="$hint" {{ $attributes }} />
