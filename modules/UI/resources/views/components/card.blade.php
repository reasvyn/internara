<x-mary-card 
    {{ $attributes->merge(['class' => 'bg-base-100 border-base-200 rounded-2xl border shadow-md transition-all hover:shadow-lg']) }}
>
    {{ $slot }}
</x-mary-card>