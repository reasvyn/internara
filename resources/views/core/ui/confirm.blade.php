@props([
    'title' => __('common.actions.confirm_action'),
    'message' => '',
    'icon' => 'o-exclamation-triangle',
    'confirmText' => __('common.actions.confirm'),
    'cancelText' => __('common.actions.cancel'),
    'confirmClass' => 'btn-error',
])

<x-mary-modal wire:model="showConfirm" :title="$title" class="backdrop-blur-sm">
    <div class="flex items-start gap-4">
        <x-mary-icon :name="$icon" class="size-6 text-warning shrink-0 mt-0.5" />
        <p class="text-sm text-base-content/80">{{ $message }}</p>
    </div>

    <x-slot:actions>
        <x-mary-button :label="$cancelText" wire:click="$set('showConfirm', false)" class="btn-ghost btn-sm" />
        <x-mary-button :label="$confirmText" wire:click="confirmAction" :class="'btn-sm ' . $confirmClass" spinner="confirmAction" />
    </x-slot:actions>
</x-mary-modal>
