@props([
    'name',
    'outline' => false,
    'solid' => false,
])

{{--
    UI Wrapper: Icon
    Wraps TallStackUI icon (<x-ts-icon>).
--}}
<x-ts-icon :name="$name" :outline="$outline" :solid="$solid" {{ $attributes }} />
