<x-ui::components.record-manager :title="__('academic_year.title')" :subtitle="__('academic_year.subtitle')">
    <x-slot:headerActions>
        <x-ts-button :text="__('academic_year.create')" icon="plus" color="primary" sm wire:click="create" />
    </x-slot:headerActions>

    <x-slot:stats>
        <x-ui::widgets.stat-card
            :title="__('academic_year.stats_total')"
            :value="$stats['total']"
            icon="calendar-days"
        />
        <x-ui::widgets.stat-card
            :title="__('academic_year.stats_total_internships')"
            :value="$stats['totalInternships']"
            icon="briefcase"
        />
        <x-ui::widgets.stat-card
            :title="__('academic_year.stats_with_internships')"
            :value="$stats['withInternships']"
            icon="building-library"
        />
    </x-slot:stats>

    <x-slot:selectionBar>
        <x-ts-button
            :text="__('academic_year.delete_selected')"
            icon="trash"
            color="red"
            sm
            wire:click="askDeleteSelected"
        />
    </x-slot:selectionBar>

    <x-ts-table
        :headers="$this->headers()"
        :rows="$years"
        :sort-by="$sortBy"
        with-pagination
        selectable
        wire:model.live="selectedIds"
        class="table-sm"
    >
        @interact('column_name', $year)
            <div class="flex items-center gap-2">
                @if ($year->is_active)
                    <span
                        class="bg-success size-2 shrink-0 rounded-full"
                        title="{{ __('academic_year.active') }}"
                    ></span>
                @else
                    <span
                        class="bg-base-content/20 size-2 shrink-0 rounded-full"
                        title="{{ __('academic_year.inactive') }}"
                    ></span>
                @endif
                <span class="text-sm font-medium">{{ $year->name }}</span>
            </div>
        @endinteract

        @interact('column_start_date', $year)
            <span class="text-sm">{{ $year->start_date?->format('d M Y') ?? '—' }}</span>
        @endinteract

        @interact('column_end_date', $year)
            <span class="text-sm">{{ $year->end_date?->format('d M Y') ?? '—' }}</span>
        @endinteract

        @interact('column_is_active', $year)
            @if ($year->is_active)
                <x-ts-badge :text="__('academic_year.active')" color="green" xs />
            @else
                <x-ts-badge :text="__('academic_year.inactive')" color="gray" xs />
            @endif
        @endinteract

        @interact('column_actions', $year)
            <x-ui::components.table-actions>
                @if (! $year->is_active)
                    <x-ui::components.table-action action="edit" wire:click="edit('{{ $year->id }}')" />
                    <x-ui::components.table-action
                        action="custom"
                        icon="check"
                        color="green"
                        wire:click="askActivate('{{ $year->id }}')"
                        :label="__('academic_year.activate')"
                    />
                    <x-ui::components.table-action action="delete" wire:click="askDestroy('{{ $year->id }}')" />
                @endif
            </x-ui::components.table-actions>
        @endinteract
    </x-ts-table>

    <x-slot:modal>
        <x-ts-modal wire="showModal" :title="$editingYearId ? __('academic_year.edit') : __('academic_year.new')" blur>
            <div class="space-y-4">
                <x-ts-input
                    :label="__('academic_year.name')"
                    wire:model="form.name"
                    :placeholder="__('academic_year.name_placeholder')"
                    icon="academic-cap"
                />
                <div class="grid grid-cols-2 gap-4">
                    <x-ts-input
                        :label="__('academic_year.start_date')"
                        type="date"
                        wire:model="form.start_date"
                        icon="calendar"
                    />
                    <x-ts-input
                        :label="__('academic_year.end_date')"
                        type="date"
                        wire:model="form.end_date"
                        icon="calendar-days"
                    />
                </div>
            </div>

            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        color="slate"
                        outline
                        sm
                        wire:click="$set('showModal', false)"
                    />
                    @if ($editingYearId)
                        <x-ts-button
                            :text="__('common.actions.update')"
                            color="primary"
                            sm
                            wire:click="update"
                            loading="update"
                        />
                    @else
                        <x-ts-button
                            :text="__('common.actions.save')"
                            color="primary"
                            sm
                            wire:click="store"
                            loading="store"
                        />
                    @endif
                </div>
            </x-slot:footer>
        </x-ts-modal>
    </x-slot:modal>
</x-ui::components.record-manager>

@include('setup.components.academic-year-guide')
