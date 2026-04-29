@props([
    'canContinue' => true,
    'alpineContinue' => null,
    'showBack' => true,
    'showSkip' => false,
    'backLabel' => null,
    'continueLabel' => null,
    'skipLabel' => null,
    'loading' => false,
    'icon' => null,
    'iconPosition' => null,
])

@php
$backLabel = $backLabel ?? __('setup::wizard.common.back');
$continueLabel = $continueLabel ?? __('setup::wizard.common.continue');
$skipLabel = $skipLabel ?? __('setup::wizard.common.skip');
@endphp

<div class="flex items-center justify-between gap-3">
    @if($showBack)
        <x-ui::button
            variant="tertiary"
            class="btn-md"
            :label="$backLabel"
            wire:click="backToPrev"
        />
    @else
        <div></div>
    @endif

    <div class="flex items-center gap-3">
        @if($showSkip)
            <x-ui::button
                variant="tertiary"
                class="btn-md"
                :label="$skipLabel"
                wire:click="skip"
            />
        @endif
        
        <x-ui::button
            variant="primary"
            class="btn-md"
            :label="$continueLabel"
            wire:click="nextStep"
            :disabled="!$canContinue"
            :attributes="$alpineContinue ? $attributes->merge(['x-bind:disabled' => '!('.$alpineContinue.')']) : $attributes"
            :spinner="$loading"
            :icon="$icon"
            :iconPosition="$iconPosition"
            wire:key="btn-next-step"
        />
    </div>
</div>