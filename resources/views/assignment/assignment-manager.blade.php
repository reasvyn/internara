<x-ui::components.record-manager :title="__('assignment.heading')" :subtitle="__('assignment.subheading')">
    <x-slot:headerActions>
        <x-ts-button :text="__('assignment.create')" icon="plus" color="primary" sm wire:click="create" />
    </x-slot:headerActions>

    <x-slot:filters>
        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('assignment.status') }}</label>
        <x-ts-select.native
            wire:model.live="filters.status"
            :options="ts_options(['draft' => __('assignment.statuses.draft'), 'published' => __('assignment.statuses.published'), 'closed' => __('assignment.statuses.closed')], __('assignment.select_status'))"
        />

        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('assignment.mandatory') }}</label>
        <x-ts-select.native
            wire:model.live="filters.is_mandatory"
            :options="ts_options(['yes' => __('assignment.mandatory'), 'no' => __('assignment.optional')], __('assignment.select_mandatory'))"
        />

        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('assignment.type') }}</label>
        <x-ts-select.native
            wire:model.live="filters.assignment_type"
            :options="ts_options(['project' => __('assignment.types.project'), 'report' => __('assignment.types.report'), 'essay' => __('assignment.types.essay')], __('assignment.select_type'))"
        />
    </x-slot:filters>

    <x-slot:selectionBar>
        <x-ts-button
            aria-label="{{ __('common.actions.delete_selected') }}"
            icon="trash"
            color="red"
            sm
            wire:click="askDeleteSelected"
        />
    </x-slot:selectionBar>

    <x-ts-table
        :headers="$this->headers()"
        :rows="$this->rows()"
        :sort-by="$sortBy"
        with-pagination
        selectable
        wire:model.live="selectedIds"
        class="table-sm"
    >
        @interact('column_title', $assignment)
            <div class="flex flex-col py-1">
                <span class="text-base-content text-sm font-medium">{{ $assignment->title }}</span>
                @if ($assignment->description)
                    <span class="text-base-content/50 mt-0.5 line-clamp-1 max-w-xs text-xs">{{ $assignment->description }}</span>
                @endif
            </div>
        @endinteract

        @interact('column_is_mandatory', $assignment)
            @if ($assignment->is_mandatory)
                <x-ts-badge :text="__('assignment.required')" color="red" xs />
            @else
                <x-ts-badge :text="__('assignment.optional')" color="gray" xs />
            @endif
        @endinteract

        @interact('column_status', $assignment)
            @php
                $badgeColor = match ($assignment->status->value) {
                    'draft' => 'white',
                    'published' => 'green',
                    'closed' => 'red',
                    default => 'white',
                };
            @endphp
            <x-ts-badge :text="$assignment->status->label()" :color="$badgeColor" xs />
        @endinteract

        @interact('column_due_date', $assignment)
            <span class="text-sm"> {{ $assignment->due_date?->format('d M Y') ?? '—' }} </span>
        @endinteract

        @interact('column_action', $assignment)
            <x-ui::components.table-actions>
                @if ($assignment->status->value === 'draft')
                    <x-ui::components.table-action
                        action="custom"
                        icon="paper-airplane"
                        color="green"
                        wire:click="publish('{{ $assignment->id }}')"
                        :label="__('assignment.publish_tooltip')"
                    />
                @endif
                <x-ui::components.table-action action="edit" wire:click="edit('{{ $assignment->id }}')" />
                <x-ui::components.table-action action="delete" wire:click="askDelete('{{ $assignment->id }}')" />
            </x-ui::components.table-actions>
        @endinteract
    </x-ts-table>

    <x-slot:modal>
        <x-ts-modal
            wire="assignmentModal"
            :title="$formData['id'] ? __('assignment.edit') : __('assignment.create')"
            separator
            blur
        >
            <div class="space-y-4">
                <x-ts-input :label="__('assignment.title')" wire:model="formData.title" icon="document-text" />

                <x-ts-select.native
                    :label="__('assignment.type')"
                    wire:model="formData.assignment_type"
                    :options="ts_options($this->assignmentTypes->pluck('name', 'id'), __('assignment.type_placeholder'))"
                />

                <x-ts-select.native
                    :label="__('assignment.internship')"
                    wire:model="formData.internship_id"
                    :options="ts_options($this->internships->pluck('name', 'id'), __('assignment.internship_placeholder'))"
                />

                <x-ts-input
                    :label="__('assignment.due_date')"
                    type="date"
                    wire:model="formData.due_date"
                    icon="calendar"
                />

                <x-ts-textarea :label="__('assignment.description')" wire:model="formData.description" rows="3" />

                <x-ts-toggle :label="__('assignment.is_mandatory')" wire:model="formData.is_mandatory" />
            </div>

            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('assignmentModal', false)"
                        color="slate"
                        outline
                        sm
                    />
                    <x-ts-button
                        :text="__('common.actions.save')"
                        type="submit"
                        color="primary"
                        wire:click="save"
                        loading="save"
                        sm
                    />
                </div>
            </x-slot:footer>
        </x-ts-modal>
    </x-slot:modal>
</x-ui::components.record-manager>
