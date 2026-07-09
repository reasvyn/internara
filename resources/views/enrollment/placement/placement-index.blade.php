<x-core::ui.record-manager
    :title="__('placement.title')"
    :subtitle="__('placement.subtitle')"
>
    <x-slot:headerActions>
        <x-mary-button :label="__('placement.add')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
    </x-slot:headerActions>

    <x-slot:extraMenu>
        <x-mary-menu-item :title="__('common.actions.import')" icon="o-arrow-up-tray" />
        <x-mary-menu-item :title="__('common.actions.export')" icon="o-arrow-down-tray" />
        <x-mary-menu-item :title="__('common.actions.template')" icon="o-document-arrow-down" />
    </x-slot:extraMenu>

    <x-slot:stats>
        <x-core::widgets.stat-card icon="o-briefcase" :title="__('placement.stats.total')" :value="$this->stats['total']" />
        <x-core::widgets.stat-card icon="o-user-group" :title="__('placement.stats.total_quota')" :value="$this->stats['total_quota']" />
        <x-core::widgets.stat-card icon="o-check-circle" :title="__('placement.stats.filled')" :value="$this->stats['filled']" color="text-success" />
        <x-core::widgets.stat-card icon="o-plus-circle" :title="__('placement.stats.available')" :value="$this->stats['available']" />
    </x-slot:stats>

    <x-slot:filters>
        <x-mary-select
            wire:model.live="filters.company_id"
            :options="$this->companies"
            :placeholder="__('placement.filter_by_company')"
            clearable
        />
        <x-mary-select
            wire:model.live="filters.internship_id"
            :options="$this->internships"
            :placeholder="__('placement.filter_by_batch')"
            clearable
        />
    </x-slot:filters>

    <x-core::ui.selection-bar>
        <x-mary-dropdown>
            <x-slot:trigger>
                <x-mary-button icon="o-chevron-down" class="btn-sm btn-primary font-medium" :label="__('common.actions.actions')" />
            </x-slot:trigger>
            <div class="p-1.5 w-48">
                <x-mary-menu-item :title="__('common.actions.delete_selected')" icon="o-trash" class="text-error"
                    wire:click="askDeleteSelected" />
            </div>
        </x-mary-dropdown>
    </x-core::ui.selection-bar>

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
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="edit('{{ $placement->id }}')" :aria-label="__('common.actions.edit')" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error"
                        wire:click="askDelete('{{ $placement->id }}')"
                        :aria-label="__('common.actions.delete')" />
                </div>
            @endscope
        </x-mary-table>
    </div>

    <x-slot:modal>
        <x-mary-modal wire:model="showModal" :title="$form->id ? __('placement.edit') : __('placement.new')" class="backdrop-blur-sm">
            <x-mary-form wire:submit="save">
                <div class="space-y-5">
                    <x-mary-input :label="__('placement.name')" wire:model="form.name" :placeholder="__('placement.name_placeholder')" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-mary-select :label="__('placement.company')" wire:model="form.company_id" :options="$this->companies" :placeholder="__('placement.select_company')" />
                        <x-mary-select :label="__('placement.batch')" wire:model="form.internship_id" :options="$this->internships" :placeholder="__('placement.select_batch')" />
                        <x-mary-input :label="__('placement.quota')" type="number" wire:model="form.quota" />
                    </div>
                    <x-mary-textarea :label="__('placement.worksite_address')" wire:model="form.address" rows="2" :placeholder="__('placement.worksite_address_placeholder')" />
                    <x-mary-textarea :label="__('placement.job_description')" wire:model="form.description" rows="3" />
                </div>
                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('placement.save')" class="btn-primary btn-sm" type="submit" spinner="save" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>

    <x-core::ui.confirm :message="__('common.actions.confirm_message')" />
@include('enrollment.placement.components.placement-guide')
</x-core::ui.record-manager>
