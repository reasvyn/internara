<div>
    <x-mary-header title="Student Management" subtitle="Manage internship students and their academic records" separator>
        <x-slot:actions>
            <x-mary-button label="Add Student" icon="o-plus" wire:click="createUser" class="btn-primary" />
        </x-slot:actions>
    </x-mary-header>

    <div class="mb-4">
        <x-mary-input label="Search" wire:model.live.debounce="search" icon="o-magnifying-glass" placeholder="Search by name, NISN, or username..." />
    </div>

    <x-mary-card>
        <x-mary-table :headers="$headers" :rows="$users" with-pagination>
            @scope('actions', $user)
                <div class="flex gap-2">
                    <x-mary-button icon="o-pencil" wire:click="editUser('{{ $user->id }}')" class="btn-sm btn-ghost" />
                    <x-mary-button icon="o-trash" wire:click="deleteUser('{{ $user->id }}')" wire:confirm="Delete this student?" class="btn-sm btn-ghost text-error" />
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    <x-mary-modal wire:model="userModal" title="{{ $userData['id'] ? 'Edit Student' : 'Add Student' }}" separator>
        <x-mary-form wire:submit="saveUser">
            <x-mary-input label="Full Name" wire:model="userData.name" icon="o-user" />
            <x-mary-input label="Email" wire:model="userData.email" icon="o-envelope" />
            <x-mary-input label="Username" wire:model="userData.username" icon="o-at-symbol" />
            <x-mary-input label="NISN" wire:model="userData.national_identifier" icon="o-identification" />
            <x-mary-input label="NIS" wire:model="userData.registration_number" icon="o-hashtag" />
            
            <x-slot:actions>
                <x-mary-button label="Cancel" wire:click="$set('userModal', false)" />
                <x-mary-button label="Save" type="submit" icon="o-check" class="btn-primary" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>
