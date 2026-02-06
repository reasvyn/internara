@props(['title' => null])

<x-ui::layouts.base.with-navbar :$title>
    <main class="flex flex-1 flex-col items-center justify-center p-4" data-aos="zoom-in" data-aos-duration="600">
        <x-honeypot />
        {{ $slot }}
    </main>
</x-ui::layouts.base.with-navbar>
