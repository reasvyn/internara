<div class="animate-in fade-in slide-in-from-bottom-8 duration-1000">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-8 gap-4">
        <div>
            <h2 class="text-3xl font-black tracking-tightest text-base-content">User Access Control</h2>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-base-content/40 mt-2">Manage all users and system permissions</p>
        </div>
        <x-mary-button label="Create User" icon="o-plus" class="btn-primary rounded-[2rem] font-black uppercase tracking-[0.2em] text-[10px] px-8 h-12 shadow-2xl shadow-primary/30 hover:scale-[1.02] transition-transform" wire:click="createUser" />
    </div>

    {{-- Controls Section --}}
    <div class="mb-8 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
        <div class="w-full lg:max-w-md relative group">
            <div class="absolute inset-0 bg-primary/5 rounded-[1.5rem] blur-md transition-opacity duration-300 opacity-0 group-focus-within:opacity-100"></div>
            <x-mary-input 
                wire:model.live.debounce.300ms="search" 
                placeholder="Search by name, email, or username..." 
                icon="o-magnifying-glass" 
                clearable 
                class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 transition-all duration-300 bg-base-200/50 focus:bg-base-100 h-14 relative z-10"
            />
        </div>
        <div class="w-full lg:w-64">
            <x-mary-select 
                wire:model.live="filters.role" 
                :options="$this->roles" 
                placeholder="Filter by Role" 
                icon="o-shield-check" 
                clearable 
                class="rounded-[1.5rem] border-base-content/5 bg-base-200/50 h-14 transition-all duration-300"
            />
        </div>
    </div>

    {{-- Selection Bar --}}
    @if($this->selected_count > 0)
        <div class="mb-8 p-4 bg-primary/5 border border-primary/20 rounded-[2rem] flex flex-col sm:flex-row items-center justify-between gap-6 animate-in fade-in slide-in-from-top-4 duration-500 shadow-xl shadow-primary/5 backdrop-blur-md">
            <div class="flex items-center gap-5 pl-2">
                <div class="size-12 rounded-[1.5rem] bg-primary text-primary-content flex items-center justify-center font-black shadow-lg shadow-primary/30 text-lg">
                    {{ $this->selected_count }}
                </div>
                <div class="text-center sm:text-left">
                    <h4 class="font-black text-sm text-primary uppercase tracking-tight">{{ __('Records Selected') }}</h4>
                    <p class="text-[9px] uppercase font-black tracking-[0.3em] opacity-50 mt-1">{{ __('Apply bulk operations') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-4 pr-2">
                <x-mary-button 
                    label="Delete Selected" 
                    icon="o-trash" 
                    class="btn-error text-white font-black uppercase tracking-widest text-[10px] rounded-xl h-10 px-6 shadow-lg shadow-error/20 hover:scale-105 transition-transform" 
                    wire:confirm="Are you sure you want to delete the selected users?"
                    wire:click="deleteSelected" 
                />
                <div class="w-px h-8 bg-primary/20 mx-2"></div>
                <x-mary-button 
                    label="{{ __('Cancel') }}" 
                    wire:click="clearSelection" 
                    class="btn-ghost rounded-xl font-black uppercase tracking-widest text-[10px] hover:bg-base-content/5" 
                />
            </div>
        </div>
    @endif

    {{-- Table Section --}}
    <x-mary-card shadow class="card-enterprise !bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5 overflow-hidden">
        <div class="table-enterprise overflow-x-auto">
            <x-mary-table 
                :headers="$this->headers()" 
                :rows="$this->rows()" 
                :sort-by="$sortBy"
                with-pagination 
                selectable
                wire:model="selectedIds"
                class="table-md w-full whitespace-nowrap"
            >
                @scope('cell_name', $user)
                    <div class="flex items-center gap-4 py-2">
                        <x-mary-avatar :title="$user->name" class="w-10 h-10 rounded-2xl shadow-sm border border-base-content/5" />
                        <div class="flex flex-col">
                            <span class="font-black text-sm tracking-tight text-base-content">{{ $user->name }}</span>
                            <span class="text-[9px] font-black uppercase tracking-[0.2em] opacity-40 mt-1">{{ $user->id }}</span>
                        </div>
                    </div>
                @endscope

                @scope('cell_email', $user)
                    <div class="flex flex-col py-2">
                        <span class="text-xs font-bold text-base-content">{{ $user->email }}</span>
                        <span class="text-[10px] font-medium opacity-50 mt-1">{{ $user->username }}</span>
                    </div>
                @endscope

                @scope('cell_roles_list', $user)
                    <div class="flex flex-wrap gap-2 py-2">
                        @foreach($user->roles as $role)
                            <x-mary-badge :value="$role->name" class="badge-ghost border border-base-content/10 bg-base-200/50 text-[9px] uppercase font-black tracking-widest px-3 py-2" />
                        @endforeach
                    </div>
                @endscope

                @scope('cell_status', $user)
                    @php
                        $status = $user->latestStatus()?->name ?? 'unknown';
                        $statusClass = match($status) {
                            'active' => 'badge-success !bg-success/10 !text-success !border-success/20',
                            'suspended' => 'badge-error !bg-error/10 !text-error !border-error/20',
                            'pending' => 'badge-warning !bg-warning/10 !text-warning-content !border-warning/20',
                            default => 'badge-ghost',
                        };
                    @endphp
                    <div class="py-2">
                        <button 
                            wire:click="toggleStatus('{{ $user->id }}')" 
                            wire:confirm="Change user status?"
                            class="badge {{ $statusClass }} font-black text-[9px] uppercase tracking-widest px-4 py-3 cursor-pointer hover:scale-105 hover:shadow-md transition-all duration-300"
                        >
                            {{ $status }}
                        </button>
                    </div>
                @endscope

                @scope('actions', $user)
                    <div class="flex items-center justify-end gap-2 py-2">
                        <x-mary-button icon="o-key" class="btn-ghost btn-sm btn-circle text-warning hover:bg-warning/10 transition-colors" wire:confirm="Reset password for this user?" wire:click="resetPassword('{{ $user->id }}')" tooltip="Reset Password" />
                        <x-mary-button icon="o-pencil" class="btn-ghost btn-sm btn-circle text-primary hover:bg-primary/10 transition-colors" wire:click="editUser('{{ $user->id }}')" tooltip="Edit User" />
                        <x-mary-button icon="o-trash" class="btn-ghost btn-sm btn-circle text-error hover:bg-error/10 transition-colors" wire:confirm="Are you sure?" wire:click="deleteUser('{{ $user->id }}')" tooltip="Delete User" />
                    </div>
                @endscope
            </x-mary-table>
        </div>
    </x-mary-card>

    {{-- Modals --}}
    <x-mary-modal wire:model="userModal" title="{{ $userData['id'] ? 'Edit User' : 'New User Account' }}" class="backdrop-blur-sm" box-class="rounded-[2.5rem] p-6 border border-base-content/5 shadow-2xl">
        <div class="space-y-6 pt-4">
            <x-mary-input label="Full Name" wire:model="userData.name" icon="o-user" class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 bg-base-200/50 py-3" />
            <x-mary-input label="Email" type="email" wire:model="userData.email" icon="o-envelope" class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 bg-base-200/50 py-3" />
            
            @if(!$userData['id'])
                <x-mary-input label="Password" type="password" wire:model="userData.password" icon="o-key" class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 bg-base-200/50 py-3" />
            @endif

            <x-mary-choices
                label="Assigned Roles"
                wire:model="userData.roles"
                :options="$this->roles"
                icon="o-shield-check"
                class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 bg-base-200/50"
            />
        </div>

        <x-slot:actions>
            <div class="flex gap-4 pt-6 border-t border-base-content/5 w-full justify-end">
                <x-mary-button label="Cancel" @click="$wire.userModal = false" class="btn-ghost rounded-[1.5rem] font-black uppercase tracking-widest text-[10px] px-8" />
                <x-mary-button label="Save User" class="btn-primary rounded-[1.5rem] font-black uppercase tracking-[0.2em] text-[10px] px-10 shadow-xl shadow-primary/20" wire:click="saveUser" spinner="saveUser" />
            </div>
        </x-slot:actions>
    </x-mary-modal>
</div>

