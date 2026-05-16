@props([])

<div x-data="{}"
     class="my-4 p-4 rounded-xl flex items-center justify-between gap-3 transition-all duration-200"
     x-show="$wire.selectedIds.length > 0"
     x-cloak
     role="status" aria-live="polite">
    <div class="flex items-center gap-3">
        {{ $slot }}
        <x-mary-button
            :label="__('common.actions.cancel')"
            wire:click="clearSelection"
            class="btn-sm btn-ghost"
        />
    </div>
    <p class="text-sm shrink-0 text-base-content/70">
        <span class="font-semibold" x-text="$wire.selectedIds.length"></span>
        <span>{{ __('common.actions.x_selected') }}</span>
    </p>
</div>
