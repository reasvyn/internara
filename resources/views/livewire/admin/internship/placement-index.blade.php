<div class="p-8">
    <x-mary-header title="Internship Placements" subtitle="Manage quotas and available positions for students" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button label="Add Placement" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow class="bg-base-100 border border-base-200">
        <div class="mb-6 flex justify-between gap-4">
            <div class="w-full max-w-sm">
                <x-mary-input wire:model.live.debounce.300ms="search" placeholder="Search position or company..." icon="o-magnifying-glass" clearable />
            </div>
        </div>

        @php
            $headers = [
                ['key' => 'name', 'label' => 'Position / Role'],
                ['key' => 'company.name', 'label' => 'Company'],
                ['key' => 'internship.name', 'label' => 'Batch'],
                ['key' => 'quota', 'label' => 'Quota'],
                ['key' => 'actions', 'label' => '', 'sortable' => false]
            ];
        @endphp

        <x-mary-table :headers="$headers" :rows="$placements" with-pagination>
            @scope('cell_quota', $placement)
                <div class="flex items-center gap-2">
                    <progress class="progress {{ $placement->isFull() ? 'progress-error' : 'progress-primary' }} w-16" value="{{ $placement->filled_quota }}" max="{{ $placement->quota }}"></progress>
                    <span class="text-xs text-base-content/70 whitespace-nowrap">{{ $placement->filled_quota }} / {{ $placement->quota }}</span>
                </div>
            @endscope

            @scope('actions', $placement)
                <div class="flex justify-end gap-2">
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm text-primary" wire:click="edit('{{ $placement->id }}')" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error" 
                        wire:confirm="Are you sure you want to delete this placement?"
                        wire:click="delete('{{ $placement->id }}')" />
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    {{-- Form Modal --}}
    <x-mary-modal wire:model="showModal" title="{{ $placementId ? 'Edit Placement' : 'New Placement' }}" separator>
        <div class="space-y-6">
            <x-mary-select label="Company" wire:model="company_id" :options="$this->companies" placeholder="Select Company" />
            <x-mary-select label="Internship Batch" wire:model="internship_id" :options="$this->internships" placeholder="Select Active Batch" />
            
            <x-mary-input label="Position Name" wire:model="name" placeholder="e.g. Frontend Developer Intern" />
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-input label="Quota (Number of Students)" type="number" wire:model="quota" />
                <x-mary-textarea label="Specific Address (if different from HQ)" wire:model="address" rows="2" />
            </div>

            <x-mary-textarea label="Requirements / Description" wire:model="description" rows="3" />
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showModal = false" />
            <x-mary-button label="Save" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-mary-modal>
</div>
