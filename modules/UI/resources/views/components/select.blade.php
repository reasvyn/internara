@props([
    'label' => null,
    'icon' => null,
    'hint' => null,
    'options' => [],
    'optionValue' => 'id',
    'optionLabel' => 'name',
    'placeholder' => null,
])

<div class="w-full">
    @if($label)
        <label class="label mb-1 px-1">
            <span class="label-text font-semibold text-base-content/80">{{ $label }}</span>
        </label>
    @endif

    <x-mary-select
        {{ $attributes->merge(['class' => 'select-bordered focus:border-accent focus:ring-accent w-full']) }}
        :options="$options"
        :option-value="$optionValue"
        :option-label="$optionLabel"
        :placeholder="$placeholder ?? __('ui::common.select_option')"
        :icon="$icon"
        :hint="$hint"
        aria-label="{{ $label ?? $placeholder ?? __('ui::common.select_option') }}"
    />
</div>