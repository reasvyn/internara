@props([
    'title',
    'subtitle' => null,
])

<div class="py-4">
    {{-- Header --}}
    <div class="mb-6 flex items-start sm:items-center justify-between flex-col sm:flex-row gap-4">
        <div>
            <h2 class="text-xl font-bold">{{ $title }}</h2>
            @if($subtitle)
                <p class="text-sm text-base-content/50 mt-1">{{ $subtitle }}</p>
            @endif
        </div>
        @if(isset($headerActions) || isset($extraMenu))
            <div class="flex items-center gap-3">
                {{ $headerActions ?? '' }}
                @if(isset($extraMenu))
                    <x-mary-dropdown>
                        <x-slot:trigger>
                            <x-mary-button icon="o-ellipsis-vertical" class="btn-ghost btn-sm [&>span>svg]:!stroke-[2.5]" :aria-label="__('common.actions.more')" />
                        </x-slot:trigger>
                        <div class="p-1.5 w-48">
                            {{ $extraMenu }}
                        </div>
                    </x-mary-dropdown>
                @endif
            </div>
        @elseif(isset($actions))
            <div class="flex items-center gap-3">
                {{ $actions }}
            </div>
        @endif
    </div>

    {{-- Stats --}}
    @if(isset($stats))
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {{ $stats }}
        </div>
    @endif

    {{-- Search + Filters Dropdown --}}
    <x-mary-card class="bg-base-100 border border-base-content/10 mb-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <x-mary-input
                wire:model.live.debounce.300ms="search"
                :placeholder="__('common.actions.search')"
                icon="o-magnifying-glass"
                clearable
                class="sm:max-w-xs"
                aria-label="{{ __('common.actions.search') }}"
            />
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 text-sm text-base-content/60">
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
                        <div x-show="filtersOpen" x-on:click.outside="filtersOpen = false" class="absolute right-0 mt-2 p-4 space-y-4 w-80 bg-base-100 border border-base-content/10 rounded-xl shadow-xl z-50" x-cloak>
                            {{ $filters }}

                            <x-mary-button :label="__('common.actions.reset_filters')" icon="o-x-mark" class="btn-ghost btn-sm w-full" wire:click="resetFilters" x-on:click="filtersOpen = false" />
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </x-mary-card>

    {{-- Main Content (Table) --}}
    {{ $slot }}

    {{-- Modal --}}
    @if(isset($modal))
        {{ $modal }}
    @endif
</div>
