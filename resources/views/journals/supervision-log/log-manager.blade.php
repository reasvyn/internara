<div class="p-8">
    <x-mary-header
        :title="__('journals.supervision.title')"
        :subtitle="__('journals.supervision.subtitle')"
        separator
        progress-indicator
    >
        <x-slot:actions>
            <x-mary-button
                :label="__('journals.supervision.log_new')"
                icon="o-plus"
                class="btn-primary"
                wire:click="create"
            />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow class="bg-base-100 border-base-200 border">
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

        <x-mary-table :headers="$headers" :rows="$logs" with-pagination>
            @scope('cell_date', $log)
                {{ $log->date->format('d M Y') }}
            @endscope

            @scope('cell_type', $log)
                <x-mary-badge
                    :value="ucfirst($log->type)"
                    :class="$log->type === 'guidance' ? 'badge-primary' : 'badge-secondary'"
                />
            @endscope

            @scope('cell_status', $log)
                @if ($log->is_verified)
                    <x-mary-badge :value="__('journals.verified')" class="badge-success" />
                @else
                    <x-mary-badge :value="__('journals.pending')" class="badge-neutral" />
                @endif
            @endscope

            @scope('actions', $log)
                @if (! $log->is_verified)
                    <x-mary-button
                        :label="__('journals.verify')"
                        icon="o-check"
                        class="btn-ghost btn-sm text-success"
                        wire:click="verify('{{ $log->id }}')"
                    />
                @endif
            @endscope
        </x-mary-table>
    </x-mary-card>

    {{-- Form Modal --}}
    <x-mary-modal wire:model="showModal" :title="__('journals.supervision.log_session')" separator>
        <div class="space-y-6">
            <x-mary-select
                :label="__('journals.student')"
                wire:model="registrationId"
                :options="$this->students->map(fn ($r) => ['id' => $r->id, 'name' => $r->student->name])"
                :placeholder="__('journals.supervision.select_student')"
            />

            <x-mary-datepicker :label="__('journals.date')" wire:model="date" icon="o-calendar" />

            <x-mary-input
                :label="__('journals.topic')"
                wire:model="topic"
                :placeholder="__('journals.supervision.placeholder_topic')"
            />

            <x-mary-textarea
                :label="__('journals.supervision.session_notes')"
                wire:model="notes"
                rows="4"
                :placeholder="__('journals.supervision.placeholder_notes')"
            />
        </div>

        <x-slot:actions>
            <x-mary-button :label="__('common.actions.cancel')" @click="$wire.showModal = false" />
            <x-mary-button
                :label="__('journals.supervision.record_session')"
                class="btn-primary"
                wire:click="save"
                spinner="save"
            />
        </x-slot:actions>
    </x-mary-modal>
</div>
