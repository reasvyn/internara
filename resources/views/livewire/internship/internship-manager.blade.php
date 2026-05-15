<x-ui::record-manager
    :title="__('internship.title')"
    :subtitle="__('internship.subtitle')"

>
    <x-slot:headerActions>
        <x-mary-button :label="__('internship.create_batch')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
    </x-slot:headerActions>

    <x-slot:extraMenu>
        <x-mary-menu-item :title="__('common.actions.import')" icon="o-arrow-up-tray" />
        <x-mary-menu-item :title="__('common.actions.export')" icon="o-arrow-down-tray" />
        <x-mary-menu-item :title="__('common.actions.template')" icon="o-document-arrow-down" />
    </x-slot:extraMenu>

    <x-slot:stats>
        <x-widget::stat icon="o-calendar" :label="__('internship.stats.total')" :value="$this->stats['total']" />
        <x-widget::stat icon="o-play" :label="__('internship.stats.active')" :value="$this->stats['active']" />
        <x-widget::stat icon="o-briefcase" :label="__('internship.stats.total_placements')" :value="$this->stats['total_placements']" />
        <x-widget::stat icon="o-user-group" :label="__('internship.stats.total_registrations')" :value="$this->stats['total_registrations']" />
    </x-slot:stats>

    <x-slot:filters>
        <x-mary-select
            wire:model.live="filters.status"
            :placeholder="__('internship.status')"
            :options="['active' => 'Active', 'published' => 'Published', 'completed' => 'Completed', 'draft' => 'Draft', 'cancelled' => 'Cancelled']"
            class="sm:max-w-xs"
        />
    </x-slot:filters>

    <x-ui::selection-bar>
        <x-mary-dropdown>
            <x-slot:trigger>
                <x-mary-button icon="o-chevron-down" class="btn-sm btn-primary font-medium" label="Actions" />
            </x-slot:trigger>
            <div class="p-1.5 w-48">
                <x-mary-menu-item title="Delete Selected" icon="o-trash" class="text-error"
                    wire:confirm="Delete selected internship batches? Only empty batches will be deleted."
                    wire:click="deleteSelected" />
                <x-mary-menu-item title="Complete All Filtered" icon="o-check-circle"
                    wire:confirm="Set all filtered internship batches to COMPLETED? Continue?"
                    wire:click="closeAllFiltered" />
            </div>
        </x-mary-dropdown>
    </x-ui::selection-bar>

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
            @scope('cell_start_date', $internship)
                <span class="text-sm font-medium">{{ $internship->start_date->format('d M Y') }}</span>
            @endscope

            @scope('cell_end_date', $internship)
                <span class="text-sm font-medium">{{ $internship->end_date->format('d M Y') }}</span>
            @endscope

            @scope('cell_status', $internship)
                @php
                    $statusClass = match($internship->status->value) {
                        'active' => 'badge-success',
                        'published' => 'badge-info',
                        'completed' => 'badge-neutral',
                        'cancelled' => 'badge-error',
                        default => 'badge-ghost',
                    };
                @endphp
                <x-mary-badge :value="__('internship.statuses.' . $internship->status->value)" class="{{ $statusClass }} font-bold text-[10px] uppercase tracking-tighter" />
            @endscope

            @scope('actions', $internship)
                <div class="flex justify-end gap-1">
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="edit('{{ $internship->id }}')" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error"
                        wire:confirm="{{ __('internship.delete_confirm') }}"
                        wire:click="delete('{{ $internship->id }}')" />
                </div>
            @endscope
        </x-mary-table>
    </div>

    <x-slot:modal>
        <x-mary-modal wire:model="showModal" :title="$formData['id'] ? __('internship.edit_batch') : __('internship.new_batch')" separator class="backdrop-blur-sm">
            <div class="space-y-6">
                <x-mary-input :label="__('internship.name')" wire:model="formData.name" :placeholder="__('internship.name_placeholder')" icon="o-academic-cap" class="rounded-xl border-base-300" />
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-mary-datepicker :label="__('internship.start_date')" wire:model="formData.start_date" icon="o-calendar" class="rounded-xl border-base-300" />
                    <x-mary-datepicker :label="__('internship.end_date')" wire:model="formData.end_date" icon="o-calendar" class="rounded-xl border-base-300" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-mary-datepicker :label="__('internship.registration_start_date')" wire:model="formData.registration_start_date" icon="o-clock" class="rounded-xl border-base-300" />
                    <x-mary-datepicker :label="__('internship.registration_end_date')" wire:model="formData.registration_end_date" icon="o-clock" class="rounded-xl border-base-300" />
                </div>
                <x-mary-select :label="__('internship.status')" wire:model="formData.status" :options="$this->statusOptions" icon="o-flag" class="rounded-xl border-base-300" />
                <x-mary-textarea :label="__('internship.description')" wire:model="formData.description" :placeholder="__('internship.description_placeholder')" rows="2" icon="o-document-text" class="rounded-xl border-base-300" />
            </div>
            <x-slot:actions>
                <x-mary-button :label="__('internship.cancel')" @click="$wire.showModal = false" class="rounded-xl" />
                <x-mary-button :label="__('internship.save')" class="btn-primary rounded-xl font-bold uppercase tracking-widest" wire:click="save" spinner="save" />
            </x-slot:actions>
        </x-mary-modal>
    </x-slot:modal>
</x-ui::record-manager>
