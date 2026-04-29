<div>
    <x-mary-header title="Teacher Management" subtitle="Manage academic supervisors and teachers" separator>
        <x-slot:actions>
            <x-mary-button label="Add Teacher" icon="o-plus" wire:click="createUser" class="btn-primary" />
        </x-slot:actions>
    </x-mary-header>

    <div class="mb-4">
        <x-mary-input label="Search" wire:model.live.debounce="search" icon="o-magnifying-glass" placeholder="Search by name or email..." />
    </div>

    <x-mary-card>
        <x-mary-table :headers="$headers" :rows="$users" with-pagination>
            @scope('actions', $user)
                <div class="flex gap-2">
                    <x-mary-button icon="o-pencil" wire:click="editUser('{{ $user->id }}')" class="btn-sm btn-ghost" />
                    <x-mary-button icon="o-trash" wire:click="deleteUser('{{ $user->id }}')" wire:confirm="Delete this teacher?" class="btn-sm btn-ghost text-error" />
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    <x-mary-modal wire:model="userModal" title="{{ $userData['id'] ? 'Edit Teacher' : 'Add Teacher' }}" separator>
        <x-mary-form wire:submit="saveUser">
            <x-mary-input label="Full Name" wire:model="userData.name" icon="o-user" />
            <x-mary-input label="Email" wire:model="userData.email" icon="o-envelope" />
            <x-mary-input label="NIP / Registration Number" wire:model="userData.registration_number" icon="o-hashtag" />
            
            <x-slot:actions>
                <x-mary-button label="Cancel" wire:click="$set('userModal', false)" />
                <x-mary-button label="Save" type="submit" icon="o-check" class="btn-primary" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>
