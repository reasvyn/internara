@props(['title' => null])

<x-ui::layouts.base.with-navbar :$title>
    {{ $slot }}
</x-ui::layouts.base.with-navbar>