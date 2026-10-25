@php
    $customization = $classes();
    $inputBase = str_replace('py-1.5', 'px-3 py-2', $customization['input.base']);
@endphp

<x-dynamic-component :component="TallStackUi::prefix('wrapper.input')" :$id :$property :$error :$label :$hint
                     :$invalidate>
    <div x-data="tallstackui_formTextArea(@js($resizeAuto), @js($customization['count.max']))">
        <div @class([
            $customization['input.wrapper'],
            $customization['input.color.base'] => !$error,
            $customization['input.color.background'] => !$attributes->get('disabled') && !$attributes->get('readonly'),
            $customization['input.color.disabled'] => $attributes->get('disabled') || $attributes->get('readonly'),
            $customization['error'] => $error,
        ])>
            <textarea @if ($id) id="{{ $id }}" @endif
            x-ref="textarea"
                      @if ($count) x-on:keyup="counter()" @endif
                      @if ($resizeAuto) x-on:input="resize()" @endif
                    {{ $attributes->class([
                        'resize-none' => !$resize && !$resizeAuto,
                        $inputBase,
                    ])->merge(['rows' => 3]) }}>{{ $attributes->get('value', $slot) }}</textarea>
        </div>
        @if ($count)
            <span class="{{ $customization['count.base'] }}" x-ref="counter"></span>
        @endif
    </div>
</x-dynamic-component>
