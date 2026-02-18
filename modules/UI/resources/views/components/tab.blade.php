
<x-mary-tab {{ $attributes->merge(['class' => 'rounded-xl transition-all', 'role' => 'tab']) }}>
    <div class="py-4" >
        {{ $slot }}
    </div>
</x-mary-tab>
