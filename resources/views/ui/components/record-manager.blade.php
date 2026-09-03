@props([
    'title',
    'subtitle' => null,
])

<div class="space-y-6 py-4">
    {{-- Header --}}
    <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">{{ $title }}</h1>
            @if ($subtitle)
                <p class="text-base-content/50 mt-1 text-sm">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="flex shrink-0 items-center gap-3">
            {{ $headerActions ?? '' }}
            @if (isset($extraMenu))
                <x-ts-dropdown position="bottom-end">
                    <x-slot:action>
                        <x-ts-button.circle
                            icon="ellipsis-vertical"
                            color="white"
                            sm
                            :aria-label="__('common.actions.more')"
                        / x-on:click="show = ! show">
                    </x-slot:action>
                    <div class="w-48 p-1.5">{{ $extraMenu }}</div>
                </x-ts-dropdown>
            @endif
        </div>
    </div>

    {{-- Stats --}}
    @if (isset($stats))
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">{{ $stats }}</div>
    @endif

    {{-- Search + Filters --}}
    <div class="bg-base-100 border-base-content/10 flex flex-col items-start justify-between gap-4 rounded-xl border p-4 sm:flex-row sm:items-center">
        <x-ts-input
            wire:model.live.debounce.300ms="search"
            :placeholder="__('common.actions.search')"
            icon="magnifying-glass"
            clearable
            class="w-full sm:max-w-xs"
            aria-label="{{ __('common.actions.search') }}"
        />
        <div class="flex w-full items-center gap-3 sm:w-auto">
            <label class="text-base-content/60 flex items-center gap-2 text-sm whitespace-nowrap">
                <span>{{ __('common.pagination.per_page') }}</span>
                <x-ts-select.native wire:model.live="perPage" class="w-20 text-sm">
                    @foreach ($this->perPageOptions() as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </x-ts-select.native>
            </label>
            @if (isset($filters))
                <div x-data="{ filtersOpen: false }" class="relative">
                    <x-ts-button
                        icon="adjustments-horizontal"
                        color="slate"
                        outline
                        sm
                        :text="__('common.actions.filters')"
                        x-on:click="filtersOpen = ! filtersOpen"
                        x-bind:aria-expanded="filtersOpen"
                    />
                    <div
                        x-show="filtersOpen"
                        x-on:click.outside="filtersOpen = false"
                        class="bg-base-100 border-base-content/10 absolute right-0 z-50 mt-2 w-80 space-y-4 rounded-xl border p-4 shadow-xl"
                        x-cloak
                        x-trap="filtersOpen"
                    >
                        {{ $filters }}
                        <x-ts-button
                            :text="__('common.actions.reset_filters')"
                            icon="x-mark"
                            color="slate" outline
                            sm
                            class="w-full"
                            wire:click="resetFilters"
                            x-on:click="filtersOpen = false"
                        />
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Selection Bar --}}
    @if (isset($selectionBar))
        <div
            x-data="{ show: @entangle('selectedIds') }"
            x-show="show.length > 0"
            x-cloak
            class="bg-primary/5 border-primary/20 flex items-center justify-between rounded-xl border px-4 py-3"
        >
            <span
                class="text-primary text-sm font-medium"
                x-text="`{{ __('common.pagination.selected_count') }}`.replace(':count', show.length)"
            ></span>
            <div class="flex items-center gap-2">{{ $selectionBar }}</div>
        </div>
    @endif

    {{-- Table --}}
    <div class="border-base-content/10 overflow-x-auto rounded-xl border">{{ $slot }}</div>

    {{-- Empty State --}}
    @if (isset($emptyState))
        {{ $emptyState }}
    @endif

    {{-- Modals --}}
    @if (isset($modal))
        {{ $modal }}
    @endif
</div>
