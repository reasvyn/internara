@props([
    'text' => null,
    'icon' => null,
    'href' => null,
])

{{--
    UI Wrapper: Dropdown Item
    Wraps TallStackUI dropdown items (<x-ts-dropdown.items>).
--}}
<x-ts-dropdown.items :text="$text" :icon="$icon" :href="$href" {{
    $attributes->class([
        'rounded-xl',
    ])
}}>
    {{ $slot }}
</x-ts-dropdown.items>
