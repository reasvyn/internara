<x-core::ui.record-manager
    :title="__('assessment.presentation_title')"
    :subtitle="__('assessment.presentation_subtitle')"
>
    <x-slot:headerActions>
        <x-ts-button :text="__('assessment.presentation_add')" icon="plus" color="primary" sm wire:click="create" />
    </x-slot:headerActions>

    <x-slot:filters>
        <x-ts-select.native
            wire:model.live="filters.status"
            :options="[null => __('assessment.presentation_status')] + (collect($statusOptions)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->toArray())"
        />
    </x-slot:filters>

    <div class="overflow-x-auto">
        <x-ts-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            class="table-sm"
        >
            @interact('column_status', $p)
                <x-ts-badge
                    :text="$p->status->label()"
                    :class="match($p->status->value) {
                    'scheduled' => 'badge-info', 'completed' => 'badge-success', 'cancelled' => 'badge-error', default => 'badge-ghost',
                }"
                />
            @endinteract

            @interact('column_scheduled_at', $p)
                <span class="text-sm">{{ $p->scheduled_at?->format('d M Y H:i') ?? '—' }}</span>
            @endinteract

            @interact('column_presentation_score', $p)
                <span class="text-sm">{{ $p->presentation_score ?? '—' }}</span>
            @endinteract

            @interact('column_final_score', $p)
                <span class="text-sm font-medium">{{ $p->final_score ?? '—' }}</span>
            @endinteract

            @interact('column_action', $p)
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
            @endinteract
        </x-ts-table>
    </div>

    <x-slot:modal>
        <x-ts-modal
            wire="showScheduleModal"
            :title="__('assessment.presentation_schedule_title')"
            class="max-w-lg blur"
        >
            <form wire:submit="saveSchedule">
                <div class="space-y-5">
                    <x-ts-select.native
                        :label="__('assessment.presentation_registration')"
                        wire:model="scheduleData.registration_id"
                        :options="[null => __('assessment.presentation_registration_placeholder')] + ($this->activeRegistrations ?? [])"
                        option-label="name"
                        option-value="id"
                    />
                    <x-ts-input
                        :label="__('assessment.presentation_scheduled_at')"
                        wire:model="scheduleData.scheduled_at"
                        type="datetime-local"
                    />
                    <x-ts-input :label="__('assessment.presentation_location')" wire:model="scheduleData.location" />
                    <x-ts-select.native
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
