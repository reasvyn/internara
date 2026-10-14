@props([
    'blur' => true,
])

{{--
    UI Wrapper: Dialog
    Wraps TallStackUI dialog confirmation system (<x-ts-dialog>).
--}}
<x-ts-dialog :blur="$blur" {{ $attributes }} />
