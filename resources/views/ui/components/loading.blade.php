@props([
    'blur' => true,
    'opacity' => true,
])

{{--
    UI Wrapper: Loading Overlay
    Wraps TallStackUI loading (<x-ts-loading>).
--}}
<x-ts-loading :blur="$blur" :opacity="$opacity" {{ $attributes }} />
