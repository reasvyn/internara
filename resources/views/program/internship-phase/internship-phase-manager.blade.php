<x-core::ui.record-manager
    title="PKL Schedule / Phases"
    subtitle="Configure the timeline for this internship period"
>
    <x-slot:headerActions>
        <x-mary-button label="New Phase" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
    </x-slot:headerActions>

    <div class="overflow-x-auto">
        <x-mary-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            class="table-sm"
        >
            @scope('cell_start_date', $phase)
                <span class="text-sm">{{ $phase->start_date->format('d M Y') }}</span>
            @endscope

            @scope('cell_end_date', $phase)
                <span class="text-sm">{{ $phase->end_date->format('d M Y') }}</span>
            @endscope

            @scope('actions', $phase)
                <div class="flex justify-end gap-1">
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="edit('{{ $phase->id }}')" aria-label="Edit" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:click="askDelete('{{ $phase->id }}')" aria-label="Delete" />
                </div>
            @endscope
        </x-mary-table>
    </div>

    <x-core::ui.confirm
        wire:model="showConfirm"
        :message="$confirmMessage"
        confirmText="Confirm"
        cancelText="Cancel"
        confirmClass="btn-error"
    />

    <x-slot:modal>
        <x-mary-modal wire:model="showModal" :title="$confirmTarget ? 'Edit Phase' : 'New Phase'" class="backdrop-blur-sm">
            <x-mary-form wire:submit="save">
                <div class="space-y-5">
                    <x-mary-input label="Name" wire:model="form.name" required />
                    <x-mary-textarea label="Description" wire:model="form.description" rows="2" />
                    <div class="grid grid-cols-2 gap-4">
                        <x-mary-input label="Start Date" type="date" wire:model="form.start_date" required />
                        <x-mary-input label="End Date" type="date" wire:model="form.end_date" required />
                    </div>
                    <x-mary-input label="Color (hex)" wire:model="form.color" placeholder="#3b82f6" />
                </div>
                <x-slot:actions>
                    <x-mary-button label="Cancel" wire:click="$set('showModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button label="Save" class="btn-primary btn-sm" type="submit" spinner="save" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>
</x-core::ui.record-manager>
