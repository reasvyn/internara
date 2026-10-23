@props([])

<div
    x-data="{ selectedIds: @entangle('selectedIds').live }"
    x-show="(selectedIds || []).length > 0"
    x-cloak
    class="bg-primary/5 border-primary/20 flex items-center justify-between gap-3 rounded-2xl border px-3.5 py-2.5 transition-all duration-200 sm:px-4 sm:py-3"
    role="status"
    aria-live="polite"
>
    <div class="flex items-center gap-2">
        {{ $slot }}
        <x-ts-button
            wire:click="clearSelection"
            x-on:click="
                selectedIds = [];
                $dispatch('clear-selection');
            "
            sm
            color="slate"
            outline
            icon="x-mark"
            :text="__('common.actions.cancel')"
        />
    </div>
    <p class="text-base-content/70 text-sm whitespace-nowrap">
        <span class="text-primary font-semibold" x-text="(selectedIds || []).length"></span>
        <span>{{ __('common.actions.x_selected') }}</span>
    </p>
</div>
