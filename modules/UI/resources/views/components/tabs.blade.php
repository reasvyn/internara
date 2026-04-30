@props([
    'title' => null,
])

<div {{ $attributes->merge(['class' => 'bg-base-100 border border-base-200 shadow-md rounded-2xl overflow-hidden p-4']) }}>
    <x-mary-tabs 
        {{ $attributes->only(['wire:model', 'wire:model.live', 'wire:model.blur']) }}
        class="bg-base-200/50 mt-4 px-4 rounded-xl border border-base-200/50"
        role="tablist"
    >
        {{ $slot }}
    </x-mary-tabs>
</div>
