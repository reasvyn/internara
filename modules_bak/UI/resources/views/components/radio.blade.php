@props([
    'label' => null,
    'hint' => null,
    'options' => [],
    'optionValue' => 'id',
    'optionLabel' => 'name',
])

<div class="w-full space-y-2" >
    @if($label)
        <label class="label mb-1 px-1">
            <span class="label-text font-semibold text-base-content/80">{{ $label }}</span>
        </label>
    @endif

    <div class="flex flex-col gap-2">
        <x-mary-radio
            {{ $attributes->merge(['class' => 'radio-accent']) }}
            :options="$options"
            :option-value="$optionValue"
            :option-label="$optionLabel"
            :hint="$hint"
        />
    </div>
</div>