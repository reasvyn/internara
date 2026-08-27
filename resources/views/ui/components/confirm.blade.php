@props([
    'title' => __('common.actions.confirm_action'),
    'message' => '',
    'icon' => 'exclamation-triangle',
    'confirmText' => __('common.actions.confirm'),
    'cancelText' => __('common.actions.cancel'),
    'confirmClass' => 'btn-error',
])

@php $tsIcon = str_starts_with($icon, 'o-') ? substr($icon, 2) : $icon; @endphp

<x-ts-modal wire="showConfirm" :title="$title" blur>
    <div class="flex items-start gap-4">
        <x-ts-icon :name="$tsIcon" class="text-warning mt-0.5 size-6 shrink-0" />
        <p class="text-base-content/80 text-sm">{{ $message }}</p>
    </div>

    <x-slot:footer>
        <div class="flex justify-end gap-2">
            <x-ts-button :text="$cancelText" color="white" sm wire:click="$set('showConfirm', false)" />
            <x-ts-button :text="$confirmText" color="red" sm wire:click="confirmAction" wire:loading.attr="disabled" />
        </div>
    </x-slot:footer>
</x-ts-modal>
