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
                            <x-mary-button icon="o-ellipsis-horizontal" class="btn-ghost btn-sm" :aria-label="__('common.actions.more')" />
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
                :placeholder="__('Search records...')"
                icon="o-magnifying-glass"
                clearable
                class="sm:max-w-xs"
                aria-label="{{ __('Search records...') }}"
            />
            @if(isset($filters))
                <x-mary-dropdown>
                    <x-slot:trigger>
                        <x-mary-button icon="o-adjustments-horizontal" class="btn-ghost btn-sm" :label="__('common.actions.filters')" />
                    </x-slot:trigger>
                    <div class="p-4 space-y-4 w-72">
                        {{ $filters }}
                    </div>
                </x-mary-dropdown>
            @endif
        </div>
    </x-mary-card>

    {{-- Main Content (Table) --}}
    {{ $slot }}

    {{-- Modal --}}
    @if(isset($modal))
        {{ $modal }}
    @endif
</div>
