<x-mary-tab {{ $attributes->merge(['class' => 'rounded-xl transition-all']) }}>
    <div class="py-4 animate-in fade-in slide-in-from-bottom-2 duration-300">
        {{ $slot }}
    </div>
</x-mary-tab>
