<x-core::ui.record-manager
    :title="__('assessment.presentation_title')"
    :subtitle="__('assessment.presentation_subtitle')"
>
    <x-slot:headerActions>
        <x-ts-button :text="__('assessment.presentation_add')" icon="plus" color="primary" sm wire:click="create" />
    </x-slot:headerActions>

    <x-slot:filters>
        <x-mary-select
            wire:model.live="filters.status"
            :placeholder="__('assessment.presentation_status')"
            :options="collect($statusOptions)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->toArray()"
        />
    </x-slot:filters>

    <div class="overflow-x-auto">
        <x-mary-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            class="table-sm"
        >
            @scope('cell_status', $p)
                <x-ts-badge
                    :text="$p->status->label()"
                    :class="match($p->status->value) {
                    'scheduled' => 'badge-info', 'completed' => 'badge-success', 'cancelled' => 'badge-error', default => 'badge-ghost',
                }"
                />
            @endscope

            @scope('cell_scheduled_at', $p)
                <span class="text-sm">{{ $p->scheduled_at?->format('d M Y H:i') ?? '—' }}</span>
            @endscope

            @scope('cell_presentation_score', $p)
                <span class="text-sm">{{ $p->presentation_score ?? '—' }}</span>
            @endscope

            @scope('cell_final_score', $p)
                <span class="text-sm font-medium">{{ $p->final_score ?? '—' }}</span>
            @endscope

            @scope('actions', $p)
                <div class="flex justify-end gap-1">
                    @if ($p->status->value === 'scheduled')
                        <x-ts-button
                            aria-label="{{ __('common.actions.edit') }}"
                            icon="pencil"
                            color="white"
                            sm
                            wire:click="setupScoring('{{ $p->id }}')"
                        />
                    @endif
                </div>
            @endscope
        </x-mary-table>
    </div>

    <x-slot:modal>
        <x-ts-modal
            wire="showScheduleModal"
            :title="__('assessment.presentation_schedule_title')"
            class="max-w-lg blur"
        >
            <form wire:submit="saveSchedule">
                <div class="space-y-5">
                    <x-mary-select
                        :label="__('assessment.presentation_registration')"
                        wire:model="scheduleData.registration_id"
                        :placeholder="__('assessment.presentation_registration_placeholder')"
                        :options="$this->activeRegistrations ?? []"
                        option-label="name"
                        option-value="id"
                    />
                    <x-ts-input
                        :label="__('assessment.presentation_scheduled_at')"
                        wire:model="scheduleData.scheduled_at"
                        type="datetime-local"
                    />
                    <x-ts-input :label="__('assessment.presentation_location')" wire:model="scheduleData.location" />
                    <x-mary-select
                        :label="__('assessment.presentation_examiners')"
                        wire:model="scheduleData.examiner_ids"
                        :options="$this->teachers"
                        option-label="name"
                        option-value="id"
                        multiple
                    />
                    <x-ts-textarea
                        :label="__('assessment.presentation_notes')"
                        wire:model="scheduleData.notes"
                        rows="2"
                    />
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('showScheduleModal', false)"
                        color="white"
                        sm
                    />
                    <x-ts-button
                        :text="__('assessment.presentation_schedule')"
                        color="primary"
                        sm
                        type="submit"
                        loading="saveSchedule"
                    />
                </div>
            </form>
        </x-ts-modal>
    </x-slot:modal>
</x-core::ui.record-manager>
