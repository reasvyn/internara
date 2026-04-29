<x-mary-tab {{ $attributes->merge(['class' => 'rounded-xl transition-all', 'role' => 'tab']) }}>
    <div>
        {{ $slot }}
    </div>
</x-mary-tab>
