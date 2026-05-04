<div class="p-8">
    {{-- Header Section --}}
    <x-mary-header title="Placement Management" subtitle="Manage industry partner internship slots" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button label="Add Placement" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-mary-header>

    {{-- Stats Header --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <x-mary-stat :value="$this->stats['total']" title="Total Placements" icon="o-briefcase" class="rounded-[2rem] bg-base-100 border border-base-200" />
        <x-mary-stat :value="$this->stats['total_quota']" title="Total Quota" icon="o-user-group" class="rounded-[2rem] bg-base-100 border border-base-200" />
        <x-mary-stat :value="$this->stats['filled']" title="Filled" icon="o-check-circle" icon-class="text-success" class="rounded-[2rem] bg-base-100 border border-base-200" />
        <x-mary-stat :value="$this->stats['available']" title="Available" icon="o-plus-circle" icon-class="text-primary" class="rounded-[2rem] bg-base-100 border border-base-200" />
    </div>

    {{-- Controls Section --}}
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
        <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
            <x-mary-select 
                wire:model.live="filters.company_id" 
                :options="$this->companies" 
                placeholder="Filter by Company" 
                icon="o-building-office" 
                clearable 
                class="rounded-xl border-base-300"
            />
            <x-mary-select 
                wire:model.live="filters.internship_id" 
                :options="$this->internships" 
                placeholder="Filter by Batch" 
                icon="o-calendar" 
                clearable 
                class="rounded-xl border-base-300"
            />
        </div>
    </div>

    {{-- Selection Bar --}}
    @if($this->selected_count > 0)
        <div class="mb-6 p-4 bg-primary/5 border border-primary/20 rounded-[2rem] flex flex-col sm:flex-row items-center justify-between gap-4 animate-in fade-in slide-in-from-top-2 duration-500 shadow-xl shadow-primary/5">
            <div class="flex items-center gap-4">
                <div class="size-12 rounded-2xl bg-primary text-primary-content flex items-center justify-center font-black shadow-lg shadow-primary/20">
                    {{ $this->selected_count }}
                </div>
                <div class="text-center sm:text-left">
                    <h4 class="font-black text-sm text-primary uppercase tracking-tight">{{ __('Records Selected') }}</h4>
                    <p class="text-[10px] uppercase font-black tracking-widest opacity-40">{{ __('Apply bulk operations') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex gap-2">
                    <x-mary-button 
                        label="Delete Selected" 
                        icon="o-trash" 
                        class="btn-sm btn-error text-white font-bold rounded-lg" 
                        wire:confirm="Delete selected placements? Only placements without students will be deleted."
                        wire:click="deleteSelected" 
                    />
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
                :headers="$this->headers()" 
                :rows="$this->rows()" 
                :sort-by="$sortBy"
                with-pagination 
                selectable
                wire:model="selectedIds"
                class="table-sm"
            >
                @scope('cell_quota', $placement)
                    <span class="font-bold">{{ $placement->quota }}</span>
                @endscope

                @scope('cell_filled_quota', $placement)
                    <div class="flex items-center gap-2">
                        <x-mary-progress value="{{ ($placement->filled_quota / $placement->quota) * 100 }}" class="progress-primary h-2 w-16" />
                        <span class="text-xs font-mono">{{ $placement->filled_quota }}</span>
                    </div>
                @endscope

                @scope('actions', $placement)
                    <div class="flex justify-end gap-1">
                        <x-mary-button icon="o-pencil" class="btn-ghost btn-sm text-primary" wire:click="edit('{{ $placement->id }}')" />
                        <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error"
                            wire:confirm="Delete this placement?"
                            wire:click="delete('{{ $placement->id }}')" />
                    </div>
                @endscope
            </x-mary-table>
        </div>
    </x-mary-card>

    {{-- Form Modal --}}
    <x-mary-modal wire:model="showModal" title="{{ $formData['id'] ? 'Edit Placement' : 'New Placement' }}" separator>
        <div class="space-y-6">
            <x-mary-input label="Placement Name" wire:model="formData.name" placeholder="e.g. Frontend Web Developer" icon="o-briefcase" class="rounded-xl border-base-300" />
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-select label="Partner Company" wire:model="formData.company_id" :options="$this->companies" placeholder="Select Company" icon="o-building-office" class="rounded-xl border-base-300" />
                <x-mary-select label="Internship Batch" wire:model="formData.internship_id" :options="$this->internships" placeholder="Select Batch" icon="o-calendar" class="rounded-xl border-base-300" />
                
                <x-mary-input label="Quota" type="number" wire:model="formData.quota" icon="o-user-group" class="rounded-xl border-base-300" />
            </div>

            <x-mary-textarea label="Worksite Address (Optional)" wire:model="formData.address" rows="2" placeholder="Leave empty to use company address" icon="o-map-pin" class="rounded-xl border-base-300" />
            <x-mary-textarea label="Job Description" wire:model="formData.description" rows="3" icon="o-document-text" class="rounded-xl border-base-300" />
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showModal = false" class="rounded-xl" />
            <x-mary-button label="Save Placement" class="btn-primary rounded-xl font-bold uppercase tracking-widest" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-mary-modal>
</div>
