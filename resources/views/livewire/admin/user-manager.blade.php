<div class="p-8">
    <x-layouts.manager 
        title="User Access Control" 
        subtitle="Manage all users and system permissions" 
        :rows="$this->rows()" 
        :headers="$this->headers()"
        :selected-count="$this->selected_count"
        :sort-by="$sortBy"
    >
        {{-- Top Actions --}}
        <x-slot:actions>
            <x-mary-button label="Create User" icon="o-plus" class="btn-primary" wire:click="createUser" />
        </x-slot:actions>

        {{-- Filters --}}
        <x-slot:filters>
            <x-mary-select 
                wire:model.live="filters.role" 
                :options="$this->roles" 
                placeholder="Filter by Role" 
                icon="o-shield-check" 
                clearable 
                class="rounded-xl border-base-300"
            />
        </x-slot:filters>

        {{-- Bulk Actions --}}
        <x-slot:bulkActions>
            <x-mary-button 
                label="Delete Selected" 
                icon="o-trash" 
                class="btn-sm btn-error text-white font-bold rounded-lg" 
                wire:confirm="Are you sure you want to delete the selected users?"
                wire:click="deleteSelected" 
            />
        </x-slot:bulkActions>

        {{-- Table Cell Overrides --}}
        @scope('cell_name', $user)
            <div class="flex items-center gap-3">
                <x-mary-avatar :title="$user->name" class="w-9 h-9" />
                <div class="flex flex-col">
                    <span class="font-medium text-sm">{{ $user->name }}</span>
                    <span class="text-[10px] opacity-40 font-mono tracking-tight">{{ $user->id }}</span>
                </div>
            </div>
        @endscope

        @scope('cell_email', $user)
            <div class="flex flex-col">
                <span class="text-xs font-bold">{{ $user->email }}</span>
                <span class="text-[10px] opacity-50">{{ $user->username }}</span>
            </div>
        @endscope

        @scope('cell_roles_list', $user)
            <div class="flex flex-wrap gap-1">
                @foreach($user->roles as $role)
                    <x-mary-badge :value="$role->name" class="badge-ghost text-[10px] uppercase font-bold" />
                @endforeach
            </div>
        @endscope

        @scope('cell_status', $user)
            @php
                $status = $user->latestStatus()?->name ?? 'unknown';
                $statusClass = match($status) {
                    'active' => 'badge-success',
                    'suspended' => 'badge-error',
                    'pending' => 'badge-warning',
                    default => 'badge-ghost',
                };
            @endphp
            <x-mary-button 
                wire:click="toggleStatus('{{ $user->id }}')" 
                wire:confirm="Change user status?"
                class="badge {{ $statusClass }} border-none font-black text-[10px] uppercase cursor-pointer hover:scale-105 transition-transform"
            >
                {{ $status }}
            </x-mary-button>
        @endscope

        @scope('actions', $user)
            <div class="flex justify-end gap-1">
                <x-mary-button icon="o-key" class="btn-ghost btn-sm text-warning" wire:confirm="Reset password for this user?" wire:click="resetPassword('{{ $user->id }}')" />
                <x-mary-button icon="o-pencil" class="btn-ghost btn-sm text-primary" wire:click="editUser('{{ $user->id }}')" />
                <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:confirm="Are you sure?" wire:click="deleteUser('{{ $user->id }}')" />
            </div>
        @endscope
    </x-layouts.manager>

    {{-- Modals --}}
    <x-mary-modal wire:model="userModal" title="{{ $userData['id'] ? 'Edit User' : 'New User Account' }}" separator>
        <div class="space-y-6">
            <x-mary-input label="Full Name" wire:model="userData.name" icon="o-user" class="rounded-xl border-base-300" />
            <x-mary-input label="Email" type="email" wire:model="userData.email" icon="o-envelope" class="rounded-xl border-base-300" />
            
            @if(!$userData['id'])
                <x-mary-input label="Password" type="password" wire:model="userData.password" icon="o-key" class="rounded-xl border-base-300" />
            @endif

            <x-mary-choices
                label="Assigned Roles"
                wire:model="userData.roles"
                :options="$this->roles"
                icon="o-shield-check"
                class="rounded-xl border-base-300"
            />
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.userModal = false" class="rounded-xl" />
            <x-mary-button label="Save User" class="btn-primary rounded-xl font-bold uppercase tracking-widest" wire:click="saveUser" spinner="saveUser" />
        </x-slot:actions>
    </x-mary-modal>
</div>
