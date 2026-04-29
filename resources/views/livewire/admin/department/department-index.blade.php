<div class="p-8">
    <x-mary-header title="Departments" subtitle="Manage academic organizational units (Jurusan)" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button label="Add Department" icon="o-plus" class="btn-primary" wire:click="create" />
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
                ['key' => 'name', 'label' => 'Department Name'],
                ['key' => 'description', 'label' => 'Description'],
                ['key' => 'created_at', 'label' => 'Created'],
                ['key' => 'actions', 'label' => '', 'sortable' => false]
            ];
        @endphp

        <x-mary-table :headers="$headers" :rows="$departments" with-pagination>
            @scope('cell_description', $department)
                <span class="text-sm text-base-content/70">
                    {{ Str::limit($department->description ?? '-', 50) }}
                </span>
            @endscope

            @scope('cell_created_at', $department)
                <span class="text-sm">
                    {{ $department->created_at->format('M d, Y') }}
                </span>
            @endscope

            @scope('actions', $department)
                <div class="flex justify-end gap-2">
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm text-primary" wire:click="edit('{{ $department->id }}')" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error" 
                        wire:confirm="Are you sure you want to delete this department?"
                        wire:click="delete('{{ $department->id }}')" />
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    {{-- Form Modal --}}
    <x-mary-modal wire:model="showModal" title="{{ $departmentId ? 'Edit Department' : 'New Department' }}" separator>
        <div class="space-y-6">
            <x-mary-input label="Department Name" wire:model="name" placeholder="e.g. Rekayasa Perangkat Lunak" />
            <x-mary-textarea label="Description" wire:model="description" rows="3" />
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showModal = false" />
            <x-mary-button label="Save" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-mary-modal>
</div>
