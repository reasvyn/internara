@props([])

<div x-data="{}"
     x-show="$wire.selectedIds.length > 0"
     x-cloak
     class="bg-primary/5 border border-primary/20 rounded-xl px-4 py-3 flex items-center justify-between gap-3 transition-all duration-200"
     role="status" aria-live="polite">
    <div class="flex items-center gap-2">
        {{ $slot }}
        <x-mary-button
            wire:click="clearSelection"
            class="btn-sm btn-ghost"
            icon="o-x-mark"
            :label="__('common.actions.cancel')"
        />
    </div>
    <p class="text-sm text-base-content/70 whitespace-nowrap">
        <span class="font-semibold text-primary" x-text="$wire.selectedIds.length"></span>
        <span>{{ __('common.actions.x_selected') }}</span>
    </p>
</div>
