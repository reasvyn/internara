@props(['title' => null, 'footer' => null, 'bodyClass' => 'bg-base-100'])

<x-ui::layouts.base :$title :$bodyClass>
    <div class="flex flex-1 flex-col">
        <x-ui::navbar sticky full-width />

        <x-ui::main with-nav full-width>
            {{ $slot }}

            <x-slot:footer class="mt-auto">
                {{ $footer }}

                <x-ui::footer />
            </x-slot:footer>
        </x-ui::main>
    </div>
</x-ui::layouts.base>
