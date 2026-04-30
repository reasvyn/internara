<div class="p-8">
    <x-mary-header title="User Management" subtitle="Manage all system users, roles, and account status" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button label="Add User" icon="o-plus" class="btn-primary" wire:click="createUser" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow class="bg-base-100 border border-base-200">
        <div class="mb-6 flex flex-col md:flex-row justify-between gap-4">
            <div class="w-full max-w-sm">
                <x-mary-input wire:model.live.debounce.300ms="search" placeholder="Search by name, email, or username..." icon="o-magnifying-glass" clearable />
            </div>
            <div class="flex gap-2">
                <x-mary-select wire:model.live="filters.role" :options="$roles" placeholder="Filter by Role" icon="o-funnel" clearable />
            </div>
        </div>

        <x-mary-table :headers="$headers" :rows="$users" with-pagination>
            @scope('cell_name', $user)
                <div class="flex items-center gap-3">
                    <x-mary-avatar :title="$user->name" class="w-9 h-9" />
                    <div class="font-medium text-sm">{{ $user->name }}</div>
                </div>
            @endscope

            @scope('cell_email', $user)
                <div class="flex flex-col">
                    <span class="text-sm">{{ $user->email }}</span>
                    <span class="text-xs opacity-50">{{ $user->username }}</span>
                </div>
            @endscope

            @scope('cell_roles', $user)
                <div class="flex flex-wrap gap-1">
                    @foreach($user->roles as $role)
                        <x-mary-badge :value="$role->name" class="badge-outline text-[10px]" />
                    @endforeach
                </div>
            @endscope

            @scope('cell_status', $user)
                @php
                    $status = $user->latestStatus()?->name ?? 'active';
                    $color = match($status) {
                        'active' => 'badge-success',
                        'suspended' => 'badge-error',
                        'inactive' => 'badge-warning',
                        default => 'badge-neutral'
                    };
                @endphp
                <x-mary-badge :value="ucfirst($status)" :class="$color" />
            @endscope

            @scope('actions', $user)
                <div class="flex justify-end gap-1">
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm text-primary" wire:click="editUser('{{ $user->id }}')" />
                    
                    <x-mary-button 
                        icon="o-key" 
                        class="btn-ghost btn-sm text-warning" 
                        wire:confirm="Reset password for this user? A temporary password will be shown."
                        wire:click="resetPassword('{{ $user->id }}')" />

                    @if($user->id !== auth()->id())
                        <x-mary-button 
                            icon="{{ ($user->latestStatus()?->name ?? 'active') === 'active' ? 'o-lock-closed' : 'o-lock-open' }}" 
                            class="btn-ghost btn-sm {{ ($user->latestStatus()?->name ?? 'active') === 'active' ? 'text-error' : 'text-success' }}" 
                            wire:click="toggleStatus('{{ $user->id }}')" />
                        
                        <x-mary-button 
                            icon="o-trash" 
                            class="btn-ghost btn-sm text-error" 
                            wire:confirm="Are you sure you want to delete this user?"
                            wire:click="deleteUser('{{ $user->id }}')" />
                    @endif
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    {{-- Form Modal --}}
    <x-mary-modal wire:model="userModal" title="{{ $userData['id'] ? 'Edit User' : 'New User' }}" separator>
        <div class="space-y-6">
            <x-mary-input label="Full Name" wire:model="userData.name" />
            <x-mary-input label="Email Address" type="email" wire:model="userData.email" />
            <x-mary-input label="Username" wire:model="userData.username" />
            
            @if(!$userData['id'])
                <x-mary-input label="Temporary Password" type="password" wire:model="userData.password" hint="User should change this after first login" />
            @endif

            <x-mary-choices
                label="Assign Roles"
                wire:model="userData.roles"
                :options="$roles"
                placeholder="Select roles..." />
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.userModal = false" />
            <x-mary-button label="Save User" class="btn-primary" wire:click="saveUser" spinner="saveUser" />
        </x-slot:actions>
    </x-mary-modal>
</div>
