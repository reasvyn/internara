@props([
    'label' => null,
    'hint' => null,
    'icon' => null,
])

<div class="w-full">
    @if($label)
        <label class="label mb-1 px-1">
            <span class="label-text font-semibold text-base-content/80">{{ $label }}</span>
        </label>
    @endif

    <x-mary-textarea
        {{ $attributes->merge(['class' => 'textarea-bordered focus:border-accent focus:ring-accent w-full']) }}
        :hint="$hint"
        :icon="$icon"
        aria-label="{{ $label ?? $attributes->get('placeholder') }}"
    />
</div>
