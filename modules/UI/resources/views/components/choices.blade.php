@props([
    'label' => null,
    'icon' => null,
    'hint' => null,
])

<div class="w-full" x-on:keydown.escape.stop x-on:keyup.escape.stop data-prevent-modal-escape data-ui-choices>
    @if($label)
        <label class="label mb-1 px-1">
            <span class="label-text font-semibold text-base-content/80">{{ $label }}</span>
        </label>
    @endif

    <x-mary-choices
        {{ $attributes->merge(['class' => 'select-bordered bg-base-100 text-base-content focus:border-accent focus:ring-accent w-full']) }}
        :icon="$icon"
        :hint="$hint"
        aria-label="{{ $label ?? $attributes->get('placeholder') }}"
    />
</div>
