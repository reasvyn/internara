<x-shared::ui.record-manager
    :title="__('placement.title')"
    :subtitle="__('placement.subtitle')"
>
    <x-slot:headerActions>
        <x-mary-button label="Add Placement" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
    </x-slot:headerActions>

    <x-slot:extraMenu>
        <x-mary-menu-item :title="__('common.actions.import')" icon="o-arrow-up-tray" />
        <x-mary-menu-item :title="__('common.actions.export')" icon="o-arrow-down-tray" />
        <x-mary-menu-item :title="__('common.actions.template')" icon="o-document-arrow-down" />
    </x-slot:extraMenu>

    <x-slot:stats>
        <x-widget::stat icon="o-briefcase" label="Total Placements" :value="$this->stats['total']" />
        <x-widget::stat icon="o-user-group" label="Total Quota" :value="$this->stats['total_quota']" />
        <x-widget::stat icon="o-check-circle" label="Filled" :value="$this->stats['filled']" color="success" />
        <x-widget::stat icon="o-plus-circle" label="Available" :value="$this->stats['available']" />
    </x-slot:stats>

    <x-slot:filters>
        <x-mary-select
            wire:model.live="filters.company_id"
            :options="$this->companies"
            placeholder="Filter by Company"
            clearable
        />
        <x-mary-select
            wire:model.live="filters.internship_id"
            :options="$this->internships"
            placeholder="Filter by Batch"
            clearable
        />
    </x-slot:filters>

    <x-shared::ui.selection-bar>
        <x-mary-dropdown>
            <x-slot:trigger>
                <x-mary-button icon="o-chevron-down" class="btn-sm btn-primary font-medium" label="Actions" />
            </x-slot:trigger>
            <div class="p-1.5 w-48">
                <x-mary-menu-item title="Delete Selected" icon="o-trash" class="text-error"
                    wire:confirm="Delete selected placements? Only placements without students will be deleted."
                    wire:click="deleteSelected" />
            </div>
        </x-mary-dropdown>
    </x-shared::ui.selection-bar>

    <div class="overflow-x-auto">
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
                <span class="font-medium">{{ $placement->quota }}</span>
            @endscope

            @scope('cell_filled_quota', $placement)
                <div class="flex items-center gap-2">
                    <x-mary-progress value="{{ ($placement->filled_quota / $placement->quota) * 100 }}" class="progress-primary h-2 w-16" />
                    <span class="text-xs font-mono">{{ $placement->filled_quota }}</span>
                </div>
            @endscope

            @scope('actions', $placement)
                <div class="flex justify-end gap-1">
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="edit('{{ $placement->id }}')" aria-label="Edit" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error"
                        wire:confirm="Delete this placement?"
                        wire:click="delete('{{ $placement->id }}')"
                        aria-label="Delete" />
                </div>
            @endscope
        </x-mary-table>
    </div>

    <x-slot:modal>
        <x-mary-modal wire:model="showModal" title="{{ $formData['id'] ? 'Edit Placement' : 'New Placement' }}" class="backdrop-blur-sm">
            <x-mary-form wire:submit="save">
                <div class="space-y-5">
                    <x-mary-input label="Placement Name" wire:model="formData.name" placeholder="e.g. Frontend Web Developer" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-mary-select label="Partner Company" wire:model="formData.company_id" :options="$this->companies" placeholder="Select Company" />
                        <x-mary-select label="Internship Batch" wire:model="formData.internship_id" :options="$this->internships" placeholder="Select Batch" />
                        <x-mary-input label="Quota" type="number" wire:model="formData.quota" />
                    </div>
                    <x-mary-textarea label="Worksite Address (Optional)" wire:model="formData.address" rows="2" placeholder="Leave empty to use company address" />
                    <x-mary-textarea label="Job Description" wire:model="formData.description" rows="3" />
                </div>
                <x-slot:actions>
                    <x-mary-button label="Cancel" wire:click="$set('showModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button label="Save Placement" class="btn-primary btn-sm" type="submit" spinner="save" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>
</x-shared::ui.record-manager>
