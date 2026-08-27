<x-ui::ui.record-manager :title="__('journals.student_log_title')" :subtitle="__('journals.student_log_subtitle')">
    <x-slot:headerActions>
        <x-ts-button :text="__('journals.new_log')" icon="plus" color="primary" sm wire:click="create" />
    </x-slot:headerActions>

    <x-ts-table :headers="$this->headers()" :rows="$this->rows()" :sort-by="$sortBy" with-pagination class="table-sm">
        @interact('column_status', $l)
            <x-ts-badge
                :text="$l->status->label()"
                :class="match($l->status->value) {
                'draft' => 'badge-ghost',
                'submitted' => 'badge-info',
                'reviewed' => 'badge-success',
                'acknowledged' => 'badge-primary',
                default => 'badge-ghost',
            }"
            />
        @endinteract

        @interact('column_supervisor_feedback', $l)
            <span class="text-sm">{{ $l->supervisor_feedback ?? '—' }}</span>
        @endinteract

        @interact('column_action', $l)
            <div class="flex justify-end gap-1">
                @if ($l->status->value === 'draft')
                    <x-ts-button
                        icon="trash"
                        class="text-error"
                        color="white"
                        sm
                        wire:click="askDelete('{{ $l->id }}')"
                        :aria-label="__('common.actions.delete')"
                    />
                @endif
            </div>
        @endinteract
    </x-ts-table>

    <x-slot:modal>
        <x-ts-modal wire="showModal" :title="__('journals.new_log')" separator blur>
            <form wire:submit="save" class="space-y-5">
                <x-ts-select.native
                    :label="__('journals.supervisor')"
                    wire:model="supervisorId"
                    :options="$this->supervisors->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])"
                />
                <x-ts-input :label="__('journals.date')" wire:model="date" type="date" icon="calendar" />
                <x-ts-input :label="__('journals.topic')" wire:model="topic" />
                <x-ts-textarea :label="__('journals.notes')" wire:model="notes" rows="4" />

                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('showModal', false)"
                        color="white"
                        sm
                    />
                    <x-ts-button :text="__('common.actions.save')" color="primary" sm type="submit" loading="save" />
                </div>
            </form>
        </x-ts-modal>
    </x-slot:modal>
</x-ui::ui.record-manager>
