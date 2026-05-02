<div class="p-8">
    {{-- Header Section --}}
    <x-mary-header title="User Access Control" subtitle="Manage all users and system permissions" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button label="Create User" icon="o-plus" class="btn-primary" wire:click="createUser" />
        </x-slot:actions>
    </x-mary-header>

    {{-- Controls Section --}}
    <div class="mb-6 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
        <div class="w-full lg:max-w-md">
            <x-mary-input 
                wire:model.live.debounce.300ms="search" 
                placeholder="{{ __('Search records...') }}" 
                icon="o-magnifying-glass" 
                clearable 
                class="rounded-2xl border-base-300 focus:border-primary transition-all duration-300 shadow-sm"
            />
        </div>
        <div class="w-full lg:w-auto">
            <x-mary-select 
                wire:model.live="filters.role" 
                :options="$this->roles" 
                placeholder="Filter by Role" 
                icon="o-shield-check" 
                clearable 
                class="rounded-xl border-base-300"
            />
        </div>
    </div>

    {{-- Selection Bar --}}
    @if($this->selected_count > 0)
        <div class="mb-6 p-4 bg-primary/5 border border-primary/20 rounded-[2rem] flex flex-col sm:flex-row items-center justify-between gap-4 animate-in fade-in slide-in-from-top-2 duration-500 shadow-xl shadow-primary/5">
            <div class="flex items-center gap-4">
                <div class="size-12 rounded-2xl bg-primary text-primary-content flex items-center justify-center font-black shadow-lg shadow-primary/20">
                    {{ $this->selected_count }}
                </div>
                <div class="text-center sm:text-left">
                    <h4 class="font-black text-sm text-primary uppercase tracking-tight">{{ __('Records Selected') }}</h4>
                    <p class="text-[10px] uppercase font-black tracking-widest opacity-40">{{ __('Apply bulk operations') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex gap-2">
                    <x-mary-button 
                        label="Delete Selected" 
                        icon="o-trash" 
                        class="btn-sm btn-error text-white font-bold rounded-lg" 
                        wire:confirm="Are you sure you want to delete the selected users?"
                        wire:click="deleteSelected" 
                    />
                </div>
                <div class="divider divider-horizontal mx-1"></div>
                <x-mary-button 
                    label="{{ __('Cancel') }}" 
                    wire:click="clearSelection" 
                    class="btn-sm btn-ghost rounded-xl font-black uppercase tracking-widest text-[10px]" 
                />
            </div>
        </div>
    @endif

    {{-- Table Section --}}
    <x-mary-card shadow class="card-enterprise">
        <div class="table-enterprise">
            <x-mary-table 
                :headers="$this->headers()" 
                :rows="$this->rows()" 
                :sort-by="$sortBy"
                with-pagination 
                selectable
                wire:model="selectedIds"
                class="table-sm"
            >
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
            </x-mary-table>
        </div>
    </x-mary-card>

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
