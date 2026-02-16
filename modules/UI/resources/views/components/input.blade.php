@props([
    'label' => null,
    'icon' => null,
    'hint' => null,
    'aos' => null,
])

<div class="w-full" data-aos="{{ $aos }}">
    @if($label)
        <label class="label mb-1 px-1">
            <span class="label-text font-semibold text-base-content/80">{{ $label }}</span>
        </label>
    @endif

    <x-mary-input
        {{ $attributes->merge(['class' => 'input-bordered focus:border-accent focus:ring-accent w-full']) }}
        :icon="$icon"
        :hint="$hint"
        aria-label="{{ $label ?? $attributes->get('placeholder') }}"
    />
</div>
