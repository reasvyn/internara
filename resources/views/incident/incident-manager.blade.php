<x-shared::ui.record-manager
    :title="__('incident.title')"
    :subtitle="__('incident.subtitle')"
>
    <x-slot:filters>
        <x-mary-select wire:model.live="filters.type" :placeholder="__('incident.type')"
            :options="collect($typeOptions)->mapWithKeys(fn($t) => [$t->value => $t->label()])->toArray()" />
        <x-mary-select wire:model.live="filters.severity" :placeholder="__('incident.severity')"
            :options="collect($severityOptions)->mapWithKeys(fn($s) => [$s->value => $s->label()])->toArray()" />
        <x-mary-select wire:model.live="filters.status" :placeholder="__('incident.status')"
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
            @scope('cell_student_name', $i)
                <span class="font-medium text-sm">{{ $i->student_name }}</span>
            @endscope

            @scope('cell_type', $i)
                <span class="text-sm">{{ $i->type->label() }}</span>
            @endscope

            @scope('cell_severity', $i)
                <x-mary-badge :value="$i->severity->label()" :class="match($i->severity->value) {
                    'critical' => 'badge-error',
                    'high' => 'badge-warning',
                    'medium' => 'badge-info',
                    'low' => 'badge-ghost',
                    default => 'badge-ghost',
                }" />
            @endscope

            @scope('cell_status', $i)
                <x-mary-badge :value="$i->status->label()" :class="match($i->status->value) {
                    'reported' => 'badge-error',
                    'investigating' => 'badge-warning',
                    'resolved' => 'badge-info',
                    'closed' => 'badge-ghost',
                    default => 'badge-ghost',
                }" />
            @endscope

            @scope('cell_incident_date', $i)
                <span class="text-sm">{{ $i->incident_date?->format('d M Y H:i') ?? '—' }}</span>
            @endscope

            @scope('actions', $i)
                <div class="flex justify-end gap-1">
                    @if(!$i->status->isTerminal())
                        <x-mary-button icon="o-check-circle" class="btn-ghost btn-sm text-success"
                            wire:click="resolve('{{ $i->id }}')"
                            :aria-label="__('incident.resolve')" />
                    @endif
                </div>
            @endscope
        </x-mary-table>
    </div>

    <x-slot:modal>
        <x-mary-modal wire:model="showResolveModal" :title="__('incident.resolve_title')" class="backdrop-blur-sm">
            <x-mary-form wire:submit="saveResolve">
                <div class="space-y-5">
                    <x-mary-select :label="__('incident.status')" wire:model="resolveData.status"
                        :options="['resolved' => 'Resolved', 'closed' => 'Closed']" />
                    <x-mary-textarea :label="__('incident.resolution_notes')" wire:model="resolveData.resolution_notes"
                        :placeholder="__('incident.resolution_notes_placeholder')" rows="4" />
                </div>
                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showResolveModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('incident.resolve')" class="btn-primary btn-sm" type="submit" spinner="saveResolve" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>
</x-shared::ui.record-manager>
