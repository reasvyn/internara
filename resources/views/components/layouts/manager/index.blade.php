@props([
    'title',
    'subtitle' => null,
    'rows',
    'headers',
    'selectedCount' => 0,
    'filters' => null,
    'sortBy' => ['column' => 'created_at', 'direction' => 'desc'],
])

<div>
    {{-- Header Section --}}
    <x-mary-header :$title :$subtitle separator progress-indicator>
        <x-slot:actions>
            {{ $actions ?? '' }}
        </x-slot:actions>
    </x-mary-header>

    {{-- Controls Section (Search, Filter, etc) --}}
    <div class="mb-6 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
        <div class="w-full lg:max-w-md">
            <x-mary-input 
                wire:model.live.debounce.300ms="search" 
                placeholder="{{ __('Search records...') }}" 
                icon="o-magnifying-glass" 
                clearable 
                class="rounded-2xl border-base-300 focus:border-primary transition-all duration-300 shadow-sm"
            />
        </div>
        <div class="flex flex-wrap gap-2 w-full lg:w-auto">
            @if($filters)
                {{ $filters }}
            @endif
        </div>
    </div>

    {{-- Selection / Bulk / Mass Action Bar --}}
    @if($selectedCount > 0)
        <div class="mb-6 p-4 bg-primary/5 border border-primary/20 rounded-[2rem] flex flex-col sm:flex-row items-center justify-between gap-4 animate-in fade-in slide-in-from-top-2 duration-500 shadow-xl shadow-primary/5">
            <div class="flex items-center gap-4">
                <div class="size-12 rounded-2xl bg-primary text-primary-content flex items-center justify-center font-black shadow-lg shadow-primary/20">
                    {{ $selectedCount }}
                </div>
                <div class="text-center sm:text-left">
                    <h4 class="font-black text-sm text-primary uppercase tracking-tight">{{ __('Records Selected') }}</h4>
                    <p class="text-[10px] uppercase font-black tracking-widest opacity-40">{{ __('Apply bulk operations') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex gap-2">
                    {{ $bulkActions ?? '' }}
                </div>
                <div class="divider divider-horizontal mx-1"></div>
                <x-mary-button 
                    label="{{ __('Cancel') }}" 
                    wire:click="clearSelection" 
                    class="btn-sm btn-ghost rounded-xl font-black uppercase tracking-widest text-[10px]" 
                />
            </div>
        </div>
    @endif

    {{-- Table Section --}}
    <x-mary-card shadow class="card-enterprise">
        <div class="table-enterprise">
            <x-mary-table 
                :headers="$headers" 
                :rows="$rows" 
                :sort-by="$sortBy"
                with-pagination 
                selectable
                wire:model="selectedIds"
                class="table-sm"
            >
                {{-- Default Slots --}}
                @foreach($headers as $header)
                    @if(isset($header['key']) && $header['key'] !== 'actions')
                        @scope('cell_' . str_replace('.', '_', $header['key']), $record)
                            <span class="font-medium text-base-content/80">{{ $record->{$header['key']} ?? '' }}</span>
                        @endscope
                    @endif
                @endforeach

                {{-- Dynamic Slots passed from implementation --}}
                {{ $slot }}

                {{-- Standard Actions Column if not overridden --}}
                @scope('actions', $record)
                    @if(isset($recordActions))
                        {{ $recordActions($record) }}
                    @else
                        <div class="flex justify-end gap-1">
                            <x-mary-button icon="o-pencil-square" class="btn-ghost btn-sm text-primary transition-transform hover:scale-110" />
                            <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error transition-transform hover:scale-110" />
                        </div>
                    @endif
                @endscope
            </x-mary-table>
        </div>
    </x-mary-card>
    
    {{-- Mass Actions Section (Footer) --}}
    @if(isset($massActions))
        <div class="mt-8 p-8 bg-base-200/50 rounded-[2.5rem] border-2 border-base-300 border-dashed group transition-all duration-300 hover:bg-base-200">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-6">
                    <div class="size-14 rounded-[1.5rem] bg-base-100 flex items-center justify-center text-base-content/20 shadow-sm group-hover:text-primary transition-colors duration-300">
                        <x-mary-icon name="o-bolt" class="size-8" />
                    </div>
                    <div>
                        <h4 class="font-black uppercase tracking-[0.2em] text-xs text-base-content/40 group-hover:text-primary/60 transition-colors">{{ __('Mass Operations') }}</h4>
                        <p class="text-sm text-base-content/50 mt-1 font-medium">{{ __('Apply powerful actions to all records matching current filters.') }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap justify-center gap-3">
                    {{ $massActions }}
                </div>
            </div>
        </div>
    @endif
</div>
