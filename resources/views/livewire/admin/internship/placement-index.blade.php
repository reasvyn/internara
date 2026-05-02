<div class="p-8">
    <x-layouts.manager 
        title="Placement Management" 
        subtitle="Manage industry partner internship slots" 
        :rows="$this->rows()" 
        :headers="$this->headers()"
        :selected-count="$this->selected_count"
        :sort-by="$sortBy"
    >
        {{-- Stats Header --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <x-mary-stat :value="$this->stats['total']" title="Total Placements" icon="o-briefcase" class="rounded-[2rem] bg-base-100 border border-base-200" />
            <x-mary-stat :value="$this->stats['total_quota']" title="Total Quota" icon="o-user-group" class="rounded-[2rem] bg-base-100 border border-base-200" />
            <x-mary-stat :value="$this->stats['filled']" title="Filled" icon="o-check-circle" icon-class="text-success" class="rounded-[2rem] bg-base-100 border border-base-200" />
            <x-mary-stat :value="$this->stats['available']" title="Available" icon="o-plus-circle" icon-class="text-primary" class="rounded-[2rem] bg-base-100 border border-base-200" />
        </div>

        {{-- Top Actions --}}
        <x-slot:actions>
            <x-mary-button label="Add Placement" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>

        {{-- Filters --}}
        <x-slot:filters>
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
        </x-slot:filters>

        {{-- Bulk Actions --}}
        <x-slot:bulkActions>
            <x-mary-button 
                label="Delete Selected" 
                icon="o-trash" 
                class="btn-sm btn-error text-white font-bold rounded-lg" 
                wire:confirm="Delete selected placements? Only placements without students will be deleted."
                wire:click="deleteSelected" 
            />
        </x-slot:bulkActions>

        {{-- Table Cell Overrides --}}
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
    </x-layouts.manager>

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
