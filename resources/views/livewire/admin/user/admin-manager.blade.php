<div>
    <x-mary-header title="Admin Management" subtitle="Manage system administrators and supervisors" separator>
        <x-slot:actions>
            <x-mary-button label="Add Admin" icon="o-plus" wire:click="createUser" class="btn-primary" />
        </x-slot:actions>
    </x-mary-header>

    <div class="mb-4">
        <x-mary-input label="Search" wire:model.live.debounce="search" icon="o-magnifying-glass" placeholder="Search admins..." />
    </div>

    <x-mary-card>
        <x-mary-table :headers="$headers" :rows="$users" with-pagination>
            @scope('actions', $user)
                <div class="flex gap-2">
                    <x-mary-button icon="o-pencil" wire:click="editUser('{{ $user->id }}')" class="btn-sm btn-ghost" />
                    <x-mary-button icon="o-trash" wire:click="deleteUser('{{ $user->id }}')" wire:confirm="Delete this admin?" class="btn-sm btn-ghost text-error" />
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    <x-mary-modal wire:model="userModal" title="{{ $userData['id'] ? 'Edit Admin' : 'Add Admin' }}" separator>
        <x-mary-form wire:submit="saveUser">
            <x-mary-input label="Full Name" wire:model="userData.name" icon="o-user" />
            <x-mary-input label="Email" wire:model="userData.email" icon="o-envelope" />
            <x-mary-input label="Username" wire:model="userData.username" icon="o-at-symbol" />
            
            <x-slot:actions>
                <x-mary-button label="Cancel" wire:click="$set('userModal', false)" />
                <x-mary-button label="Save" type="submit" icon="o-check" class="btn-primary" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>
