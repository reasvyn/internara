@props([
    'text' => null,
    'color' => 'primary',
    'round' => true,
])

{{--
    UI Wrapper: Badge
    Wraps TallStackUI badge (<x-ts-badge>), ensuring non-sharp rounded corners (pill).
--}}
<x-ts-badge
    :text="$text"
    :color="$color"
    :round="$round"
    {{
        $attributes->class([
            'rounded-full' => ! str_contains((string) $attributes->get('class'), 'rounded-'),
        ])
    }}
>
    {{ $slot }}
</x-ts-badge>
