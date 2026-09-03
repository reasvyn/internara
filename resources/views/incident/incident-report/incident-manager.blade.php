<x-ui::components.record-manager :title="__('incident.title')" :subtitle="__('incident.subtitle')">
    <x-slot:filters>
        <x-ts-select.native
            wire:model.live="filters.type"
            :options="ts_options(collect($typeOptions)->mapWithKeys(fn ($t) => [$t->value => $t->label()])->toArray(), __('incident.type'))"
        />
        <x-ts-select.native
            wire:model.live="filters.severity"
            :options="ts_options(collect($severityOptions)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->toArray(), __('incident.severity'))"
        />
        <x-ts-select.native
            wire:model.live="filters.status"
            :options="ts_options(collect($statusOptions)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->toArray(), __('incident.status'))"
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
            @interact('column_student_name', $i)
                <span class="text-sm font-medium">{{ $i->student_name }}</span>
            @endinteract

            @interact('column_type', $i)
                <span class="text-sm">{{ $i->type->label() }}</span>
            @endinteract

            @interact('column_severity', $i)
                <x-ts-badge
                    :text="$i->severity->label()"
                    :class="match($i->severity->value) {
                    'critical' => 'badge-error',
                    'high' => 'badge-warning',
                    'medium' => 'badge-info',
                    'low' => 'badge-ghost',
                    default => 'badge-ghost',
                }"
                />
            @endinteract

            @interact('column_status', $i)
                <x-ts-badge
                    :text="$i->status->label()"
                    :class="match($i->status->value) {
                    'reported' => 'badge-error',
                    'investigating' => 'badge-warning',
                    'resolved' => 'badge-info',
                    'closed' => 'badge-ghost',
                    default => 'badge-ghost',
                }"
                />
            @endinteract

            @interact('column_incident_date', $i)
                <span class="text-sm">{{ $i->incident_date?->format('d M Y H:i') ?? '—' }}</span>
            @endinteract

            @interact('column_action', $i)
                <div class="flex justify-end gap-1">
                    @can('update', $i)
                        <x-ts-button
                            icon="pencil"
                            class="text-primary"
                            color="white"
                            sm
                            wire:click="edit('{{ $i->id }}')"
                            :aria-label="__('common.actions.edit')"
                        />
                    @endcan
                    @if (! $i->status->isTerminal())
                        <x-ts-button
                            icon="check-circle"
                            class="text-success"
                            color="white"
                            sm
                            wire:click="resolve('{{ $i->id }}')"
                            :aria-label="__('incident.resolve')"
                        />
                    @endif
                </div>
            @endinteract
        </x-ts-table>
    </div>

    <x-slot:modal>
        <x-ts-modal wire="showResolveModal" :title="__('incident.resolve_title')" blur>
            <form wire:submit="saveResolve">
                <div class="space-y-5">
                    <x-ts-select.native
                        :label="__('incident.status')"
                        wire:model="resolveData.status"
                        :options="ts_options(['resolved' => __('incident.statuses.resolved'), 'closed' => __('incident.statuses.closed')])"
                    />
                    <x-ts-textarea
                        :label="__('incident.resolution_notes')"
                        wire:model="resolveData.resolution_notes"
                        :placeholder="__('incident.resolution_notes_placeholder')"
                        rows="4"
                    />
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('showResolveModal', false)"
                        color="white"
                        sm
                    />
                    <x-ts-button
                        :text="__('incident.resolve')"
                        color="primary"
                        sm
                        type="submit"
                        loading="saveResolve"
                    />
                </div>
            </form>
        </x-ts-modal>

        <x-ts-modal wire="showEditModal" :title="__('incident.edit_title')" blur>
            <form wire:submit="saveEdit">
                <div class="space-y-5">
                    <x-ts-input
                        :label="__('incident.date')"
                        type="datetime-local"
                        wire:model="editData.incident_date"
                    />
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <x-ts-select.native
                            :label="__('incident.type')"
                            wire:model="editData.type"
                            :options="ts_options(collect($typeOptions)->mapWithKeys(fn ($t) => [$t->value => $t->label()])->toArray())"
                        />
                        <x-ts-select.native
                            :label="__('incident.severity')"
                            wire:model="editData.severity"
                            :options="ts_options(collect($severityOptions)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->toArray())"
                        />
                    </div>
                    <x-ts-textarea :label="__('incident.description')" wire:model="editData.description" rows="4" />
                    <x-ts-input
                        :label="__('incident.location')"
                        wire:model="editData.location"
                        :placeholder="__('incident.location_placeholder')"
                    />
                    <x-ts-textarea
                        :label="__('incident.action_taken')"
                        wire:model="editData.action_taken"
                        :placeholder="__('incident.action_taken_placeholder')"
                        rows="3"
                    />
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('showEditModal', false)"
                        color="white"
                        sm
                    />
                    <x-ts-button
                        :text="__('common.actions.save')"
                        color="primary"
                        sm
                        type="submit"
                        loading="saveEdit"
                    />
                </div>
            </form>
        </x-ts-modal>
    </x-slot:modal>
</x-ui::components.record-manager>
