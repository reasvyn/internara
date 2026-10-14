@props([
    'action' => null,
    'position' => 'bottom-end',
])

{{--
    UI Wrapper: Dropdown
    Wraps TallStackUI dropdown (<x-ts-dropdown>), ensuring rounded-xl container.
--}}
<x-ts-dropdown :position="$position" {{
    $attributes->class([
        'rounded-xl',
    ])
}}>
    @isset($action)
        <x-slot:action>{{ $action }}</x-slot:action>
    @endisset

    {{ $slot }}
</x-ts-dropdown>
