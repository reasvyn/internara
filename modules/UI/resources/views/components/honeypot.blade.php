@props(['fieldName' => 'contact_me'])

<div class="hidden" aria-hidden="true">
    <input 
        type="text" 
        name="{{ $fieldName }}" 
        id="{{ $fieldName }}" 
        tabindex="-1" 
        autocomplete="off"
        wire:model="{{ $fieldName }}"
    >
</div>
