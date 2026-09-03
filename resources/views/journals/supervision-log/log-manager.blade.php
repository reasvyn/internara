<div class="p-8">
    <x-ui::components.page-header
        :title="__('journals.supervision.title')"
        :description="__('journals.supervision.subtitle')"
    >
        <x-slot:actions>
            <x-ts-button :text="__('journals.supervision.log_new')" icon="plus" color="primary" wire:click="create" />
        </x-slot:actions>
    </x-ui::components.page-header>

    <x-ts-card class="bg-base-100 border-base-200 border">
        @php
            $headers = [
                ['index' => 'date', 'label' => __('journals.date')],
                ['index' => 'registration.student.name', 'label' => __('journals.student')],
                ['index' => 'type', 'label' => __('journals.supervision.type')],
                ['index' => 'topic', 'label' => __('journals.topic')],
                ['index' => 'status', 'label' => __('journals.status')],
                ['index' => 'actions', 'label' => ''],
            ];
        @endphp

        <x-ts-table :headers="$headers" :rows="$logs" paginate>
            @interact('column_date', $log)
                {{ $log->date->format('d M Y') }}
            @endinteract

            @interact('column_type', $log)
                <x-ts-badge
                    :text="ucfirst($log->type)"
                    :color="$log->type === 'guidance' ? 'primary' : 'secondary'"
                    xs
                />
            @endinteract

            @interact('column_status', $log)
                @if ($log->is_verified)
                    <x-ts-badge :text="__('journals.verified')" color="green" xs />
                @else
                    <x-ts-badge :text="__('journals.pending')" color="gray" xs />
                @endif
            @endinteract

            @interact('column_action', $log)
                @if (! $log->is_verified)
                    <x-ts-button
                        :text="__('journals.verify')"
                        icon="check"
                        class="text-success"
                        color="slate" outline
                        sm
                        wire:click="verify('{{ $log->id }}')"
                    />
                @endif
            @endinteract
        </x-ts-table>

        {{-- Form Modal --}}
    </x-ts-card>
    <x-ts-modal wire="showModal" :title="__('journals.supervision.log_session')" separator>
        <div class="space-y-6">
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
            <x-ts-button :text="__('common.actions.cancel')" @click="$wire.showModal = false" />
            <x-ts-button
                :text="__('journals.supervision.record_session')"
                color="primary"
                wire:click="save"
                loading="save"
            />
        </x-slot:footer>
    </x-ts-modal>

</div>
