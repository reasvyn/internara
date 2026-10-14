@props([
    'title' => null,
    'text' => null,
    'color' => 'primary',
    'icon' => null,
    'light' => true,
])

{{--
    UI Wrapper: Alert
    Wraps TallStackUI alert (<x-ts-alert>), enforcing canonical rounded-xl border styling.
--}}
<x-ts-alert
    :title="$title"
    :text="$text"
    :color="$color"
    :icon="$icon"
    :light="$light"
    {{
        $attributes->class([
            'rounded-xl border shadow-2xs',
        ])
    }}
>
    {{ $slot }}
</x-ts-alert>
