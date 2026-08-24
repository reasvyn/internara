<div class="p-8">
    <x-core::ui.page-header
        :title="__('journals.supervision.title')"
        :description="__('journals.supervision.subtitle')"
    >
        <x-slot:actions>
            <x-ts-button :text="__('journals.supervision.log_new')" icon="plus" color="primary" wire:click="create" />
        </x-slot:actions>
    </x-core::ui.page-header>

    <x-ts-card class="bg-base-100 border-base-200 border">
        @php
            $headers = [
                ['key' => 'date', 'label' => __('journals.date')],
                ['key' => 'registration.student.name', 'label' => __('journals.student')],
                ['key' => 'type', 'label' => __('journals.supervision.type')],
                ['key' => 'topic', 'label' => __('journals.topic')],
                ['key' => 'status', 'label' => __('journals.status')],
                ['key' => 'actions', 'label' => ''],
            ];
        @endphp

        <x-ts-table :headers="$headers" :rows="$logs" paginate>
            @interact('column_date', $log)
                {{ $log->date->format('d M Y') }}
            @endinteract

            @interact('column_type', $log)
                <x-ts-badge
                    :text="ucfirst($log->type)"
                    :class="$log->type === 'guidance' ? 'badge-primary' : 'badge-secondary'"
                />
            @endinteract

            @interact('column_status', $log)
                @if ($log->is_verified)
                    <x-ts-badge :text="__('journals.verified')" class="badge-success" />
                @else
                    <x-ts-badge :text="__('journals.pending')" class="badge-neutral" />
                @endif
            @endinteract

            @interact('column_action', $log)
                @if (! $log->is_verified)
                    <x-ts-button
                        :text="__('journals.verify')"
                        icon="check"
                        class="text-success"
                        color="white"
                        sm
                        wire:click="verify('{{ $log->id }}')"
                    />
                @endif
            @endinteract
        </x-ts-table>

        {{-- Form Modal --}}
        <x-ts-modal wire="showModal" :title="__('journals.supervision.log_session')" separator>
            <div class="space-y-6">
                <x-ts-select.native
                    :label="__('journals.student')"
                    wire:model="registrationId"
                    :options="[null => __('journals.supervision.select_student')] + ($this->students->map(fn ($r) => ['id' => $r->id, 'name' => $r->student->name]))"
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
