@props([
    'canContinue' => true,
    'showBack' => true,
    'showSkip' => false,
    'backLabel' => null,
    'continueLabel' => null,
    'skipLabel' => null,
    'isRecordExists' => true,
    'loading' => false,
    'icon' => null,
    'iconPosition' => null,
])

@php
$backLabel = $backLabel ?? __('setup::wizard.common.back');
$continueLabel = $continueLabel ?? __('setup::wizard.common.continue');
$skipLabel = $skipLabel ?? __('setup::wizard.common.skip');
$canContinue = $isRecordExists;
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
            x-bind:disabled="!$canContinue"
            :spinner="$loading"
            :icon="$icon"
            :iconPosition="$iconPosition"
        />
    </div>
</div>