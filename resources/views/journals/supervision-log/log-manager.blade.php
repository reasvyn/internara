<div class="p-8">
    <x-mary-header title="Supervision Management" subtitle="Manage bimbingan and mentoring for students" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button label="Log New Session" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow class="bg-base-100 border border-base-200">
        @php
            $headers = [
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'registration.student.name', 'label' => 'Student'],
                ['key' => 'type', 'label' => 'Type'],
                ['key' => 'topic', 'label' => 'Topic'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'actions', 'label' => '']
            ];
        @endphp

        <x-mary-table :headers="$headers" :rows="$logs" with-pagination>
            @scope('cell_date', $log)
                {{ $log->date->format('d M Y') }}
            @endscope

            @scope('cell_type', $log)
                <x-mary-badge :value="ucfirst($log->type)" :class="$log->type === 'guidance' ? 'badge-primary' : 'badge-secondary'" />
            @endscope

            @scope('cell_status', $log)
                @if($log->is_verified)
                    <x-mary-badge value="Verified" class="badge-success" />
                @else
                    <x-mary-badge value="Pending" class="badge-neutral" />
                @endif
            @endscope

            @scope('actions', $log)
                @if(!$log->is_verified)
                    <x-mary-button label="Verify" icon="o-check" class="btn-ghost btn-sm text-success" wire:click="verify('{{ $log->id }}')" />
                @endif
            @endscope
        </x-mary-table>
    </x-mary-card>

    {{-- Form Modal --}}
    <x-mary-modal wire:model="showModal" title="Log Supervision Session" separator>
        <div class="space-y-6">
            <x-mary-select 
                label="Student" 
                wire:model="registrationId" 
                :options="$this->students->map(fn($r) => ['id' => $r->id, 'name' => $r->student->name])" 
                placeholder="Select Student" />
            
            <x-mary-datepicker label="Date" wire:model="date" icon="o-calendar" />
            
            <x-mary-input label="Topic / Discussion" wire:model="topic" placeholder="e.g. Discussing project milestones" />
            
            <x-mary-textarea label="Session Notes" wire:model="notes" rows="4" placeholder="Summary of the guidance/mentoring session..." />
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showModal = false" />
            <x-mary-button label="Record Session" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-mary-modal>
</div>
