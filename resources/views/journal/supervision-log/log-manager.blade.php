<x-ui::components.record-manager
    :title="__('journals.supervision.title')"
    :subtitle="__('journals.supervision.subtitle')"
>
    <x-slot:headerActions>
        <x-ts-button :text="__('journals.supervision.log_new')" icon="plus" color="primary" sm wire:click="create" />
    </x-slot:headerActions>

    <x-ts-table :headers="$this->headers()" :rows="$logs" :sort-by="$sortBy" with-pagination class="table-sm">
        @interact('column_date', $log)
            <span class="text-sm font-medium">{{ $log->date->format('d M Y') }}</span>
        @endinteract

        @interact('column_type', $log)
            <x-ts-badge :text="ucfirst($log->type)" :color="$log->type === 'guidance' ? 'primary' : 'secondary'" xs />
        @endinteract

        @interact('column_status', $log)
            @if ($log->is_verified)
                <x-ts-badge :text="__('journals.verified')" color="green" xs />
            @else
                <x-ts-badge :text="__('journals.pending')" color="gray" xs />
            @endif
        @endinteract

        @interact('column_actions', $log)
            <x-ui::components.table-actions>
                @if (! $log->is_verified)
                    <x-ui::components.table-action
                        action="verify"
                        wire:click="verify('{{ $log->id }}')"
                        :label="__('journals.verify')"
                    />
                @endif
            </x-ui::components.table-actions>
        @endinteract
    </x-ts-table>

    <x-slot:modal>
        <x-ts-modal wire="showModal" :title="__('journals.supervision.log_session')" separator blur>
            <div class="space-y-4">
                <x-ts-select.native
                    :label="__('journals.student')"
                    wire:model="registrationId"
                    :options="ts_options($this->students->map(fn ($r) => ['id' => $r->id, 'name' => $r->student->name]), __('journals.supervision.select_student'))"
                />

                <x-ts-date :label="__('journals.date')" wire:model="date" />

                <x-ts-input
                    :label="__('journals.topic')"
                    wire:model="topic"
                    :placeholder="__('journals.supervision.placeholder_topic')"
                />

                <x-ts-textarea
                    :label="__('journals.supervision.session_notes')"
                    wire:model="notes"
                    rows="4"
                    :placeholder="__('journals.supervision.placeholder_notes')"
                />
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
                    <x-ts-button
                        :text="__('journals.supervision.record_session')"
                        color="primary"
                        sm
                        wire:click="save"
                        loading="save"
                    />
                </div>
            </x-slot:footer>
        </x-ts-modal>
    </x-slot:modal>
</x-ui::components.record-manager>
