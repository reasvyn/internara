<x-core::ui.record-manager
    :title="__('presentation.title')"
    :subtitle="__('presentation.subtitle')"
>
    <x-slot:headerActions>
        <x-mary-button :label="__('presentation.add')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
    </x-slot:headerActions>

    <x-slot:filters>
        <x-mary-select wire:model.live="filters.status" :placeholder="__('presentation.status')"
            :options="collect($statusOptions)->mapWithKeys(fn($s) => [$s->value => $s->label()])->toArray()" />
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
                <x-mary-badge :value="$p->status->label()" :class="match($p->status->value) {
                    'scheduled' => 'badge-info', 'completed' => 'badge-success', 'cancelled' => 'badge-error', default => 'badge-ghost',
                }" />
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
                    @if($p->status->value === 'scheduled')
                        <x-mary-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="setupScoring('{{ $p->id }}')" />
                    @endif
                </div>
            @endscope
        </x-mary-table>
    </div>

    <x-slot:modal>
        <x-mary-modal wire:model="showScheduleModal" :title="__('presentation.schedule_title')" class="backdrop-blur-sm max-w-lg">
            <x-mary-form wire:submit="saveSchedule">
                <div class="space-y-5">
                    <x-mary-select :label="__('presentation.registration')" wire:model="scheduleData.registration_id"
                        :placeholder="__('presentation.registration_placeholder')"
                        :options="$this->activeRegistrations ?? []"
                        option-label="name" option-value="id" />
                    <x-mary-input :label="__('presentation.scheduled_at')" wire:model="scheduleData.scheduled_at" type="datetime-local" />
                    <x-mary-input :label="__('presentation.location')" wire:model="scheduleData.location" />
                    <x-mary-select :label="__('presentation.examiners')" wire:model="scheduleData.examiner_ids"
                        :options="$this->teachers" option-label="name" option-value="id" multiple />
                    <x-mary-textarea :label="__('presentation.notes')" wire:model="scheduleData.notes" rows="2" />
                </div>
                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showScheduleModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('presentation.schedule')" class="btn-primary btn-sm" type="submit" spinner="saveSchedule" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>
</x-core::ui.record-manager>
