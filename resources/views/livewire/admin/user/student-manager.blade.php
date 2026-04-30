<div class="p-8">
    <x-mary-header title="Student Management" subtitle="Manage internship students and their profiles" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button label="Register Student" icon="o-plus" class="btn-primary" wire:click="createUser" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow class="bg-base-100 border border-base-200">
        <div class="mb-6 flex flex-col md:flex-row justify-between gap-4">
            <div class="w-full max-w-sm">
                <x-mary-input wire:model.live.debounce.300ms="search" placeholder="Search by name, NISN..." icon="o-magnifying-glass" clearable />
            </div>
            <div class="flex gap-2">
                <x-mary-select wire:model.live="filters.department_id" :options="$this->departments" placeholder="Filter by Department" icon="o-academic-cap" clearable />
            </div>
        </div>

        <x-mary-table :headers="$headers" :rows="$users" with-pagination>
            @scope('cell_name', $student)
                <div class="flex items-center gap-3">
                    <x-mary-avatar :title="$student->name" class="w-9 h-9" />
                    <div class="flex flex-col">
                        <span class="font-medium text-sm">{{ $student->name }}</span>
                        <span class="text-xs opacity-50">{{ $student->email }}</span>
                    </div>
                </div>
            @endscope

            @scope('actions', $student)
                <div class="flex justify-end gap-1">
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm text-primary" wire:click="editUser('{{ $student->id }}')" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:confirm="Are you sure?" wire:click="deleteUser('{{ $student->id }}')" />
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    {{-- Form Modal --}}
    <x-mary-modal wire:model="userModal" title="{{ $userData['id'] ? 'Edit Student' : 'New Student Registration' }}" separator>
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-input label="Full Name" wire:model="userData.name" class="md:col-span-2" />
                <x-mary-input label="Email" type="email" wire:model="userData.email" />
                <x-mary-input label="Username" wire:model="userData.username" />
                
                <x-mary-input label="NISN" wire:model="userData.national_identifier" />
                <x-mary-input label="NIS (Optional)" wire:model="userData.registration_number" />
                
                <x-mary-select 
                    label="Department" 
                    wire:model="userData.department_id" 
                    :options="$this->departments" 
                    placeholder="Select Department" 
                    class="md:col-span-2" />
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.userModal = false" />
            <x-mary-button label="Save Student" class="btn-primary" wire:click="saveUser" spinner="saveUser" />
        </x-slot:actions>
    </x-mary-modal>
</div>
