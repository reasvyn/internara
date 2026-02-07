@props(['aos' => null])

<x-mary-tabs 
    {{ $attributes->merge(['class' => 'tabs-boxed bg-base-200/50 p-1 rounded-2xl border border-base-200', 'role' => 'tablist']) }}
    :data-aos="$aos"
>
    {{ $slot }}
</x-mary-tabs>
