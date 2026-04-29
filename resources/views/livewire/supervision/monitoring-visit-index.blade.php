<div class="p-8">
    <x-mary-header title="Monitoring Visits" subtitle="Record and track school visits to industry partners" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button label="Log Visit" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow class="bg-base-100 border border-base-200">
        @php
            $headers = [
                ['key' => 'date', 'label' => 'Date'],
                ['key' => 'registration.student.name', 'label' => 'Student'],
                ['key' => 'registration.placement.company.name', 'label' => 'Company'],
                ['key' => 'notes', 'label' => 'Observation Summary'],
            ];
        @endphp

        <x-mary-table :headers="$headers" :rows="$visits" with-pagination>
            @scope('cell_date', $visit)
                {{ $visit->date->format('d M Y') }}
            @endscope

            @scope('cell_notes', $visit)
                <div class="max-w-xs truncate text-sm">
                    {{ $visit->notes }}
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    {{-- Form Modal --}}
    <x-mary-modal wire:model="showModal" title="Log Monitoring Visit" separator>
        <div class="space-y-6">
            <x-mary-select 
                label="Student / Placement" 
                wire:model="registrationId" 
                :options="$this->students->map(fn($r) => ['id' => $r->id, 'name' => $r->student->name . ' (' . $r->placement->company->name . ')'])" 
                placeholder="Select Student" />
            
            <x-mary-datepicker label="Date of Visit" wire:model="date" icon="o-calendar" />
            
            <x-mary-textarea label="Observation Notes" wire:model="notes" rows="3" placeholder="What did you observe during the visit?" />
            
            <x-mary-textarea label="Company Feedback" wire:model="company_feedback" rows="2" placeholder="Feedback from industry mentor..." />
            
            <x-mary-textarea label="Student Condition" wire:model="student_condition" rows="2" placeholder="How is the student performing/feeling?" />
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showModal = false" />
            <x-mary-button label="Record Visit" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-mary-modal>
</div>
