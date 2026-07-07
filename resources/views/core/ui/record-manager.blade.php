@props([
    'title',
    'subtitle' => null,
])

<div class="py-4 space-y-6">
    {{-- Header --}}
    <div class="flex items-start sm:items-center justify-between flex-col sm:flex-row gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">{{ $title }}</h1>
            @if($subtitle)
                <p class="text-sm text-base-content/50 mt-1">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="flex items-center gap-3 shrink-0">
            {{ $headerActions ?? '' }}
            @if(isset($extraMenu))
                <x-mary-dropdown>
                    <x-slot:trigger>
                        <x-mary-button icon="o-ellipsis-vertical" class="btn-ghost btn-sm" :aria-label="__('common.actions.more')" />
                    </x-slot:trigger>
                    <div class="p-1.5 w-48">
                        {{ $extraMenu }}
                    </div>
                </x-mary-dropdown>
            @endif
        </div>
    </div>

    {{-- Stats --}}
    @if(isset($stats))
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            {{ $stats }}
        </div>
    @endif

    {{-- Search + Filters --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-base-100 border border-base-content/10 rounded-xl p-4">
        <x-mary-input
            wire:model.live.debounce.300ms="search"
            :placeholder="__('common.actions.search')"
            icon="o-magnifying-glass"
            clearable
            class="w-full sm:max-w-xs"
            aria-label="{{ __('common.actions.search') }}"
        />
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <label class="flex items-center gap-2 text-sm text-base-content/60 whitespace-nowrap">
                <span>{{ __('common.pagination.per_page') }}</span>
                <select wire:model.live="perPage" class="select select-bordered select-sm text-sm w-20">
                    @foreach($this->perPageOptions() as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            </label>
            @if(isset($filters))
                <div x-data="{ filtersOpen: false }" class="relative">
                    <x-mary-button icon="o-adjustments-horizontal" class="btn-ghost btn-sm" :label="__('common.actions.filters')" x-on:click="filtersOpen = !filtersOpen" x-bind:aria-expanded="filtersOpen" />
                    <div x-show="filtersOpen" x-on:click.outside="filtersOpen = false" class="absolute right-0 mt-2 p-4 space-y-4 w-80 bg-base-100 border border-base-content/10 rounded-xl shadow-xl z-50" x-cloak x-trap="filtersOpen">
                        {{ $filters }}
                        <x-mary-button :label="__('common.actions.reset_filters')" icon="o-x-mark" class="btn-ghost btn-sm w-full" wire:click="resetFilters" x-on:click="filtersOpen = false" />
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Selection Bar --}}
    @if(isset($selectionBar))
        <div x-data="{ show: @entangle('selectedIds') }" x-show="show.length > 0" x-cloak class="bg-primary/5 border border-primary/20 rounded-xl px-4 py-3 flex items-center justify-between">
            <span class="text-sm font-medium text-primary" x-text="`{{ __('common.pagination.selected_count') }}`.replace(':count', show.length)"></span>
            <div class="flex items-center gap-2">
                {{ $selectionBar }}
            </div>
        </div>
    @endif

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-base-content/10">
        {{ $slot }}
    </div>

    {{-- Empty State --}}
    @if(isset($emptyState))
        {{ $emptyState }}
    @endif

    {{-- Modals --}}
    @if(isset($modal))
        {{ $modal }}
    @endif
</div>
