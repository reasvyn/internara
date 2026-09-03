<x-ui::components.record-manager :title="__('placement.title')" :subtitle="__('placement.subtitle')">
    <x-slot:headerActions>
        <x-ts-button :text="__('placement.add_placement')" icon="plus" color="primary" sm wire:click="create" />
    </x-slot:headerActions>

    <x-slot:extraMenu>
        <x-ts-dropdown.items :text="__('common.actions.import')" />
        <x-ts-dropdown.items :text="__('common.actions.export')" icon="arrow-down-tray" />
        <x-ts-dropdown.items :text="__('common.actions.template')" icon="document-arrow-down" />
    </x-slot:extraMenu>

    <x-slot:stats>
        <x-ui::widgets.stat-card icon="briefcase" :title="__('placement.stats.total')" :value="$this->stats['total']" />
        <x-ui::widgets.stat-card
            icon="user-group"
            :title="__('placement.stats.total_quota')"
            :value="$this->stats['total_quota']"
        />
        <x-ui::widgets.stat-card
            icon="check-circle"
            :title="__('placement.stats.filled')"
            :value="$this->stats['filled']"
            color="text-success"
        />
        <x-ui::widgets.stat-card
            icon="plus-circle"
            :title="__('placement.stats.available')"
            :value="$this->stats['available']"
        />
    </x-slot:stats>

    <x-slot:filters>
        <x-ts-select.native
            wire:model.live="filters.company_id"
            :options="ts_options($this->companies, __('placement.filter_by_company'))"
            clearable
        />
        <x-ts-select.native
            wire:model.live="filters.internship_id"
            :options="ts_options($this->internships, __('placement.filter_by_batch'))"
            clearable
        />
    </x-slot:filters>

    <x-ui::components.selection-bar>
        <x-ts-dropdown>
            <x-slot:action>
                <x-ts-button
                    icon="chevron-down"
                    class="font-medium"
                    color="primary"
                    sm
                    :text="__('common.actions.bulk_actions')"
                / x-on:click="show = ! show">
            </x-slot:action>
            <div class="w-48 p-1.5">
                <x-ts-dropdown.items
                    :text="__('common.actions.delete_selected')"
                    icon="trash"
                    class="text-error"
                    wire:click="askDeleteSelected"
                />
            </div>
        </x-ts-dropdown>
    </x-ui::components.selection-bar>

    <div class="overflow-x-auto">
        <x-ts-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            selectable
            wire:model="selectedIds"
            class="table-sm"
        >
            @interact('column_quota', $placement)
                <span class="font-medium">{{ $placement->quota }}</span>
            @endinteract

            @interact('column_filled_quota', $placement)
                <div class="flex items-center gap-2">
                    <x-ts-progress
                        percent="{{ ($placement->filled_quota / $placement->quota) * 100 }}"
                        class="progress-primary h-2 w-16"
                    />
                    <span class="font-mono text-xs">{{ $placement->filled_quota }}</span>
                </div>
            @endinteract

            @interact('column_action', $placement)
                <div class="flex justify-end gap-1">
                    <x-ts-button
                        icon="pencil"
                        color="white"
                        sm
                        wire:click="edit('{{ $placement->id }}')"
                        :aria-label="__('common.actions.edit')"
                    />
                    <x-ts-button
                        icon="trash"
                        class="text-error"
                        color="white"
                        sm
                        wire:click="askDelete('{{ $placement->id }}')"
                        :aria-label="__('common.actions.delete')"
                    />
                </div>
            @endinteract
        </x-ts-table>
    </div>

    <x-slot:modal>
        <x-ts-modal
            wire="showModal"
            :title="$form->id ? __('placement.edit_placement') : __('placement.new_placement')"
            blur
        >
            <form wire:submit="save">
                <div class="space-y-5">
                    <x-ts-input
                        :label="__('placement.name')"
                        wire:model="form.name"
                        :placeholder="__('placement.name_placeholder')"
                    />
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-ts-select.native
                            :label="__('placement.company')"
                            wire:model="form.company_id"
                            :options="ts_options($this->companies, __('placement.company_placeholder'))"
                        />
                        <x-ts-select.native
                            :label="__('placement.batch')"
                            wire:model="form.internship_id"
                            :options="ts_options($this->internships, __('placement.internship_placeholder'))"
                        />
                        <x-ts-input :label="__('placement.quota')" type="number" wire:model="form.quota" />
                    </div>
                    <x-ts-textarea
                        :label="__('placement.worksite_address')"
                        wire:model="form.address"
                        rows="2"
                        :placeholder="__('placement.address_placeholder')"
                    />
                    <x-ts-textarea :label="__('placement.job_description')" wire:model="form.description" rows="3" />
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('showModal', false)"
                        color="slate"
                        outline
                        sm
                    />
                    <x-ts-button :text="__('placement.save')" color="primary" sm type="submit" loading="save" />
                </div>
            </form>
        </x-ts-modal>
    </x-slot:modal>

    @include('enrollment.placement.components.placement-guide')
</x-ui::components.record-manager>
