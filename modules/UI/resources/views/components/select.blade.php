@props([
    'label' => null,
    'icon' => null,
    'hint' => null,
    'options' => [],
    'optionValue' => 'id',
    'optionLabel' => 'name',
    'placeholder' => null,
])

@php
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $errorField = $wireModel;
    $selectClasses = collect([
        'select w-full select-bordered bg-base-100 text-base-content focus:border-accent focus:ring-accent',
        $attributes->get('class'),
        $errorField && $errors->has($errorField) ? '!select-error' : null,
    ])->filter()->implode(' ');
@endphp

<div class="w-full" x-on:keydown.escape.stop x-on:keyup.escape.stop data-prevent-modal-escape>
    @if($label)
        <label class="label mb-1 px-1">
            <span class="label-text font-semibold text-base-content/80">{{ $label }}</span>
        </label>
    @endif

    <label class="{{ $selectClasses }}">
        @if($icon)
            <x-ui::icon :name="$icon" class="pointer-events-none w-4 h-4 -ml-1 opacity-40" />
        @endif

        <select
            {{ $attributes->except('class') }}
            class="grow bg-transparent outline-hidden"
            aria-label="{{ $label ?? $placeholder ?? __('ui::common.select_option') }}"
        >
            @if($placeholder)
                <option value="">{{ $placeholder ?? __('ui::common.select_option') }}</option>
            @endif

            @foreach($options as $option)
                <option
                    value="{{ data_get($option, $optionValue) }}"
                    @if(data_get($option, 'disabled')) disabled @endif
                    @if(data_get($option, 'hidden')) hidden @endif
                >
                    {{ data_get($option, $optionLabel) }}
                </option>
            @endforeach
        </select>
    </label>

    @if($errorField && $errors->has($errorField))
        @foreach($errors->get($errorField) as $message)
            @foreach(\Illuminate\Support\Arr::wrap($message) as $line)
                <div class="text-error fieldset-label">{{ $line }}</div>
                @break
            @endforeach
            @break
        @endforeach
    @endif

    @if($hint)
        <div class="fieldset-label">{{ $hint }}</div>
    @endif
</div>
