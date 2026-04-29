<div class="p-8">
    <x-mary-header title="Internship Batches" subtitle="Manage academic internship periods and configurations" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button label="Create Batch" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow class="bg-base-100 border border-base-200">
        <div class="mb-6 flex justify-between gap-4">
            <div class="w-full max-w-sm">
                <x-mary-input wire:model.live.debounce.300ms="search" placeholder="Search by name..." icon="o-magnifying-glass" clearable />
            </div>
        </div>

        @php
            $headers = [
                ['key' => 'name', 'label' => 'Batch Name'],
                ['key' => 'start_date', 'label' => 'Start Date'],
                ['key' => 'end_date', 'label' => 'End Date'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'actions', 'label' => '', 'sortable' => false]
            ];
        @endphp

        <x-mary-table :headers="$headers" :rows="$internships" with-pagination>
            @scope('cell_start_date', $internship)
                {{ $internship->start_date->format('d M Y') }}
            @endscope

            @scope('cell_end_date', $internship)
                {{ $internship->end_date->format('d M Y') }}
            @endscope

            @scope('cell_status', $internship)
                @if($internship->status === 'active')
                    <x-mary-badge value="Active" class="badge-success" />
                @elseif($internship->status === 'completed')
                    <x-mary-badge value="Completed" class="badge-neutral" />
                @else
                    <x-mary-badge value="Draft" class="badge-ghost" />
                @endif
            @endscope

            @scope('actions', $internship)
                <div class="flex justify-end gap-2">
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm text-primary" wire:click="edit('{{ $internship->id }}')" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error" 
                        wire:confirm="Are you sure you want to delete this batch? This may affect student registrations."
                        wire:click="delete('{{ $internship->id }}')" />
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    {{-- Form Modal --}}
    <x-mary-modal wire:model="showModal" title="{{ $internshipId ? 'Edit Internship Batch' : 'New Internship Batch' }}" separator>
        <div class="space-y-6">
            <x-mary-input label="Batch Name" wire:model="name" placeholder="e.g. PKL Semester Ganjil 2026/2027" />
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-datepicker label="Start Date" wire:model="start_date" icon="o-calendar" />
                <x-mary-datepicker label="End Date" wire:model="end_date" icon="o-calendar" />
            </div>

            <x-mary-select label="Status" wire:model="status" :options="[
                ['id' => 'draft', 'name' => 'Draft'],
                ['id' => 'active', 'name' => 'Active'],
                ['id' => 'completed', 'name' => 'Completed']
            ]" />

            <x-mary-textarea label="Description (Optional)" wire:model="description" rows="2" />
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showModal = false" />
            <x-mary-button label="Save" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-mary-modal>
</div>
