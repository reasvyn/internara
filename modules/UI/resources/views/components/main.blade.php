<x-mary-main {{ $attributes }}>
    @isset($sidebar)
        <x-slot:sidebar>
            {{ $sidebar }}
        </x-slot:sidebar>
    @endisset

    @isset($actions)
        <x-slot:actions>
            {{ $actions }}
        </x-slot:actions>
    @endisset

    @isset($footer)
        <x-slot:footer>
            {{ $footer }}
        </x-slot:footer>
    @endisset

    <x-slot:content>
        {{ $content ?? $slot }}
    </x-slot:content>
</x-mary-main>