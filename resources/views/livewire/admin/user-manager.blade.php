<div>
    <x-mary-header title="User Management" subtitle="Manage system users and their roles" separator>
        <x-slot:actions>
            <x-mary-button label="Add User" icon="o-plus" wire:click="createUser" class="btn-primary" />
        </x-slot:actions>
    </x-mary-header>

    <div class="flex flex-col md:flex-row gap-4 mb-4">
        <x-mary-input label="Search" wire:model.live.debounce="search" icon="o-magnifying-glass" placeholder="Search by name, email, or username..." class="flex-1" />
        <x-mary-select label="Filter by Role" wire:model.live="filters.role" :options="$roles" placeholder="All Roles" />
    </div>

    <x-mary-card>
        <x-mary-table :headers="$headers" :rows="$users" with-pagination>
            @scope('cell_roles', $user)
                <div class="flex flex-wrap gap-1">
                    @foreach($user->roles as $role)
                        <x-mary-badge :label="$role->name" class="badge-neutral" />
                    @endforeach
                </div>
            @endscope

            @scope('actions', $user)
                <div class="flex gap-2">
                    <x-mary-button icon="o-pencil" wire:click="editUser('{{ $user->id }}')" class="btn-sm btn-ghost" tooltip="Edit User" />
                    <x-mary-button icon="o-trash" wire:click="deleteUser('{{ $user->id }}')" wire:confirm="Are you sure you want to delete this user?" class="btn-sm btn-ghost text-error" tooltip="Delete User" />
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    <x-mary-modal wire:model="userModal" title="{{ $userData['id'] ? 'Edit User' : 'Add User' }}" separator>
        <x-mary-form wire:submit="saveUser">
            <x-mary-input label="Full Name" wire:model="userData.name" icon="o-user" />
            <x-mary-input label="Email" wire:model="userData.email" icon="o-envelope" />
            <x-mary-input label="Username" wire:model="userData.username" icon="o-at-symbol" />
            
            <div class="mt-4">
                <label class="label">Roles</label>
                <div class="flex flex-wrap gap-4">
                    @foreach($roles as $role)
                        <x-mary-checkbox 
                            :label="$role->name" 
                            wire:model="userData.roles" 
                            :value="$role->name" />
                    @endforeach
                </div>
                @error('userData.roles') <span class="text-error text-sm">{{ $message }}</span> @enderror
            </div>

            <x-slot:actions>
                <x-mary-button label="Cancel" wire:click="$set('userModal', false)" />
                <x-mary-button label="Save" type="submit" icon="o-check" class="btn-primary" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>
