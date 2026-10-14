<x-ui::components.dropdown {{ $attributes }}>
    @isset($action)
        <x-slot:action>{{ $action }}</x-slot:action>
    @endisset
    {{ $slot }}
</x-ui::components.dropdown>
