<x-ui::components.record-manager
    :title="__('journals.student_log_title')"
    :subtitle="__('journals.student_log_subtitle')"
>
    <x-slot:headerActions>
        <x-ts-button :text="__('journals.new_log')" icon="plus" color="primary" sm wire:click="create" />
    </x-slot:headerActions>

    <x-ts-table :headers="$this->headers()" :rows="$this->rows()" :sort-by="$sortBy" with-pagination class="table-sm">
        @interact('column_date', $l)
            <span class="text-sm">{{ $l->date?->format('d M Y') }}</span>
        @endinteract

        @interact('column_status', $l)
            <x-ts-badge
                :text="$l->status->label()"
                :color="match ($l->status->value) {
                    'submitted' => 'blue',
                    'reviewed', 'verified' => 'green',
                    'acknowledged' => 'primary',
                    default => 'gray',
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
                    :options="ts_options($this->supervisors, __('journals.select_supervisor'))"
                />
                <x-ts-input :label="__('journals.date')" wire:model="date" type="date" icon="calendar" />
                <x-ts-input :label="__('journals.topic')" wire:model="topic" />
                <x-ts-textarea :label="__('journals.notes')" wire:model="notes" rows="4" />

                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('showModal', false)"
                        color="slate"
                        outline
                        sm
                    />
                    <x-ts-button :text="__('common.actions.save')" color="primary" sm type="submit" loading="save" />
                </div>
            </form>
        </x-ts-modal>
    </x-slot:modal>
</x-ui::components.record-manager>
