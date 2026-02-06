@props(['title' => null, 'footer' => null])

<x-ui::layouts.base :$title>
    <div class="flex flex-1 flex-col" data-aos="fade-in">
        <x-ui::navbar sticky full-width />

        <x-ui::main with-nav full-width>
            <x-slot:content>
                {{ $slot }}
            </x-slot:content>

            <x-slot:footer class="mt-auto">
                {{ $footer }}

                <x-ui::footer />
            </x-slot:footer>
        </x-ui::main>
    </div>
</x-ui::layouts.base>
