{{--
    Shared confirm dialog, pulled in with @include.

    It deliberately does NOT use @props: that directive belongs to Blade
    components, and when this partial is included inside a component slot
    (x-ts-card, for example) it consumes the *enclosing* component's attribute
    bag. The stray attributes leak onto x-ts-modal below and the dialog renders
    itself open on page load.
--}}
@php
    $title ??= __('common.actions.confirm_action');
    $message ??= '';
    $icon ??= 'exclamation-triangle';
    $confirmText ??= __('common.actions.confirm');
    $cancelText ??= __('common.actions.cancel');
    $tsIcon = str_starts_with($icon, 'o-') ? substr($icon, 2) : $icon;
@endphp

<x-ts-modal wire="showConfirm" :title="$title" blur>
    <div class="flex items-start gap-4">
        <x-ts-icon :name="$tsIcon" class="text-warning mt-0.5 size-6 shrink-0" />
        <p class="text-base-content/80 text-sm">{{ $message }}</p>
    </div>

    <x-slot:footer>
        <div class="flex justify-end gap-2">
            <x-ts-button :text="$cancelText" color="slate" outline sm wire:click="$set('showConfirm', false)" />
            <x-ts-button :text="$confirmText" color="red" sm wire:click="confirmAction" wire:loading.attr="disabled" />
        </div>
    </x-slot:footer>
</x-ts-modal>
