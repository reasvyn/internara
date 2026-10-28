<x-ui::components.record-manager :title="__('logbook.title')" :subtitle="__('logbook.subtitle')">
    <x-slot:headerActions>
        <x-ts-button :text="__('logbook.new')" icon="plus" color="primary" sm wire:click="create" />
    </x-slot:headerActions>

    <x-slot:filters>
        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('logbook.status') }}</label>
        <x-ts-select.native
            wire:model.live="filters.status"
            :options="ts_options(['draft' => __('logbook.statuses.draft'), 'submitted' => __('logbook.statuses.submitted'), 'verified' => __('logbook.statuses.verified')], __('logbook.status'))"
        />

        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('logbook.verified') }}</label>
        <x-ts-select.native
            wire:model.live="filters.is_verified"
            :options="ts_options(['yes' => __('logbook.verified'), 'no' => __('logbook.unverified')], __('logbook.verified'))"
        />
    </x-slot:filters>

    <x-slot:selectionBar>
        <x-ts-button
            :text="__('common.actions.delete_selected')"
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
        @interact('column_user_name', $entry)
            <div class="flex items-center gap-3 py-1">
                <x-ui::components.avatar :user="$entry->user" size="size-8" />
                <div class="flex flex-col">
                    <span class="text-sm font-semibold">{{ $entry->user->name }}</span>
                    <span class="font-mono text-xs opacity-50">{{ $entry->user->email }}</span>
                </div>
            </div>
        @endinteract

        @interact('column_date', $entry)
            <span class="text-sm font-medium">{{ $entry->date->format('d M Y') }}</span>
        @endinteract

        @interact('column_content', $entry)
            <div class="text-base-content/70 max-w-xs truncate text-sm">{{ $entry->content }}</div>
        @endinteract

        @interact('column_status', $entry)
            <x-ts-badge
                :text="__('logbook.statuses.'.$entry->status->value)"
                class="font-bold text-xs uppercase tracking-tighter {{ $entry->status->value === 'verified' ? 'badge-success' : ($entry->status->value === 'submitted' ? 'badge-info' : ($entry->status->value === 'revision_required' ? 'badge-warning' : 'badge-ghost')) }}"
            />
        @endinteract

        @interact('column_is_verified', $entry)
            @if ($entry->is_verified)
                <x-ts-icon name="check-circle" class="text-success size-5" />
            @else
                <x-ts-icon name="x-circle" class="text-base-content/30 size-5" />
            @endif
        @endinteract

        @interact('column_supervisor_note', $entry)
            @if ($entry->supervisor_note)
                <div class="text-base-content/70 max-w-xs truncate text-sm">
                    {{ \Illuminate\Support\Str::limit($entry->supervisor_note, 60) }}
                </div>
            @else
                <span class="text-base-content/60 text-xs italic">{{ __('logbook.no_supervisor_note') }}</span>
            @endif
        @endinteract

        @interact('column_action', $entry)
            <x-ui::components.table-actions>
                @if (auth()->user()?->hasRole('supervisor'))
                    <x-ui::components.table-action
                        action="custom"
                        icon="chat-bubble-bottom-center-text"
                        color="blue"
                        wire:click="editSupervisorNote('{{ $entry->id }}')"
                        :label="__('logbook.edit_supervisor_note')"
                    />
                @endif
                <x-ui::components.table-action
                    action="verify"
                    wire:click="verify('{{ $entry->id }}')"
                    :label="__('logbook.toggle_verify')"
                />
                <x-ui::components.table-action
                    action="edit"
                    wire:click="edit('{{ $entry->id }}')"
                    :label="__('common.actions.edit')"
                />
                <x-ui::components.table-action
                    action="custom"
                    icon="document-arrow-down"
                    color="secondary"
                    :href="route('sysadmin.logbook.report', $entry->registration_id)"
                    target="_blank"
                    :label="__('logbook.download_report')"
                />
                <x-ui::components.table-action
                    action="delete"
                    wire:click="askDelete('{{ $entry->id }}')"
                    :label="__('common.actions.delete')"
                />
            </x-ui::components.table-actions>
        @endinteract
    </x-ts-table>

    <x-slot:modal>
        <x-ts-modal wire="showSupervisorNoteModal" :title="__('logbook.edit_supervisor_note')" separator blur>
            <div class="space-y-4">
                <x-ts-textarea
                    :label="__('logbook.supervisor_note')"
                    wire:model="supervisorNote"
                    :placeholder="__('logbook.supervisor_note_placeholder')"
                    rows="4"
                />
            </div>
            <x-slot:footer>
                <div class="flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        color="slate"
                        outline
                        sm
                        wire:click="$set('showSupervisorNoteModal', false)"
                    />
                    <x-ts-button
                        :text="__('logbook.save')"
                        color="primary"
                        sm
                        wire:click="saveSupervisorNote"
                        loading="saveSupervisorNote"
                    />
                </div>
            </x-slot:footer>
        </x-ts-modal>

        <x-ts-modal wire="showModal" :title="$this->form->id ? __('logbook.edit') : __('logbook.new')" separator blur>
            <div class="space-y-4">
                @if (! $this->form->id)
                    <x-ts-select.native
                        :label="__('logbook.student')"
                        wire:model="form.user_id"
                        :options="ts_options($this->students, __('logbook.select_student'))"
                    />
                @endif

                <x-ts-date :label="__('logbook.date')" wire:model="form.date" />

                <x-ts-textarea :label="__('logbook.content')" wire:model="form.content" rows="4" />

                <x-ts-textarea :label="__('logbook.learning_outcomes')" wire:model="form.learning_outcomes" rows="2" />

                <x-ts-textarea :label="__('logbook.mentor_feedback')" wire:model="form.mentor_feedback" rows="2" />
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
                    <x-ts-button :text="__('logbook.save')" color="primary" sm wire:click="save" loading="save" />
                </div>
            </x-slot:footer>
        </x-ts-modal>
    </x-slot:modal>
</x-ui::components.record-manager>
