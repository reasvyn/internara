<x-mary-card 
    {{ $attributes->merge(['class' => 'bg-base-100 border-base-200 rounded-2xl border shadow-sm transition-shadow hover:shadow-md']) }}
>
    {{ $slot }}
</x-mary-card>