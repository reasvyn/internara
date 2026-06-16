<x-core::ui.record-manager
    :title="__('guidance.student_log_title')"
    :subtitle="__('guidance.student_log_subtitle')"
>
    <x-slot:headerActions>
        <x-mary-button :label="__('guidance.new_log')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
    </x-slot:headerActions>

    <x-core::ui.confirm
        wire:model="showConfirm"
        confirmText="{{ __('common.actions.confirm') }}"
        cancelText="{{ __('common.actions.cancel') }}"
    />

    <x-mary-table
        :headers="$this->headers()"
        :rows="$this->rows()"
        :sort-by="$sortBy"
        with-pagination
        class="table-sm"
    >
        @scope('cell_status', $l)
            <x-mary-badge :value="$l->status->label()" :class="match($l->status->value) {
                'draft' => 'badge-ghost',
                'submitted' => 'badge-info',
                'reviewed' => 'badge-success',
                'acknowledged' => 'badge-primary',
                default => 'badge-ghost',
            }" />
        @endscope

        @scope('cell_supervisor_feedback', $l)
            <span class="text-sm">{{ $l->supervisor_feedback ?? '—' }}</span>
        @endscope

        @scope('actions', $l)
            <div class="flex justify-end gap-1">
                @if($l->status->value === 'draft')
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:click="askDelete('{{ $l->id }}')" :aria-label="__('common.actions.delete')" />
                @endif
            </div>
        @endscope
    </x-mary-table>

    <x-slot:modal>
        <x-mary-modal wire:model="showModal" :title="__('guidance.new_log')" separator class="backdrop-blur-sm">
            <x-mary-form wire:submit="save" class="space-y-5">
                <x-mary-select :label="__('guidance.supervisor')" wire:model="supervisorId" :options="$this->supervisors->map(fn($s) => ['id' => $s->id, 'name' => $s->name])" />
                <x-mary-input :label="__('guidance.date')" wire:model="date" type="date" icon="o-calendar" />
                <x-mary-input :label="__('guidance.topic')" wire:model="topic" />
                <x-mary-textarea :label="__('guidance.notes')" wire:model="notes" rows="4" />

                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('common.actions.save')" class="btn-primary btn-sm" type="submit" spinner="save" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>
</x-core::ui.record-manager>
