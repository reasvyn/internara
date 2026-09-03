@props([])

<div
    x-data="{}"
    x-show="$wire.selectedIds.length > 0"
    x-cloak
    class="bg-primary/5 border-primary/20 flex items-center justify-between gap-3 rounded-xl border px-4 py-3 transition-all duration-200"
    role="status"
    aria-live="polite"
>
    <div class="flex items-center gap-2">
        {{ $slot }}
        <x-ts-button wire:click="clearSelection" sm color="slate" outline icon="x-mark" :text="__('common.actions.cancel')" />
    </div>
    <p class="text-base-content/70 text-sm whitespace-nowrap">
        <span class="text-primary font-semibold" x-text="$wire.selectedIds.length"></span>
        <span>{{ __('common.actions.x_selected') }}</span>
    </p>
</div>
