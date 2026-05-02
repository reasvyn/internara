<div class="p-8">
    {{-- Header Section --}}
    <x-mary-header :title="__('user.student.title')" :subtitle="__('user.student.subtitle')" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button :label="__('user.student.new')" icon="o-plus-circle" class="btn-primary rounded-2xl font-black uppercase tracking-widest px-6 shadow-lg shadow-primary/20" wire:click="createUser" />
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
                wire:model.live="filters.department_id" 
                :options="$this->departments" 
                :placeholder="__('user.student.department')" 
                clearable 
                class="rounded-2xl border-base-300 focus:border-primary shadow-sm"
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
                        :label="__('common.actions.delete_selected')" 
                        icon="o-trash" 
                        class="btn-sm btn-error text-white font-black uppercase tracking-widest text-[10px] rounded-xl px-4 shadow-md" 
                        :wire:confirm="__('common.actions.confirm_action')"
                        wire:click="deleteSelected" 
                    />
                    <x-mary-button label="Export Data" icon="o-arrow-down-tray" class="btn-sm btn-ghost font-black uppercase tracking-widest text-[10px] rounded-xl" />
                    <x-mary-button label="Archive All Filtered" icon="o-archive-box" class="btn-ghost font-black uppercase tracking-widest text-[10px] text-warning rounded-xl border-warning/20" wire:click="archiveAllFiltered" />
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
                    <div class="flex gap-4 items-center group">
                        <x-mary-avatar :title="$user->name" class="w-10 h-10 rounded-2xl border-2 border-white shadow-md transition-transform group-hover:scale-110" />
                        <div class="flex flex-col">
                            <span class="text-sm font-black tracking-tight text-base-content">{{ $user->name }}</span>
                            <span class="text-[10px] font-medium opacity-40 uppercase tracking-widest">{{ $user->email }}</span>
                        </div>
                    </div>
                @endscope

                @scope('actions', $user)
                    <div class="flex justify-end gap-2">
                        <x-mary-button icon="o-pencil-square" class="btn-ghost btn-sm text-primary transition-transform hover:scale-110" wire:click="editUser('{{ $user->id }}')" />
                        <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error transition-transform hover:scale-110" wire:confirm="{{ __('common.actions.confirm_action') }}" wire:click="deleteUser('{{ $user->id }}')" />
                    </div>
                @endscope
            </x-mary-table>
        </div>
    </x-mary-card>

    {{-- User Modal --}}
    <x-mary-modal wire:model="userModal" :title="$userData['id'] ? __('user.student.edit') : __('user.student.new')" separator class="backdrop-blur-sm">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-4">
            <x-mary-input :label="__('user.fields.full_name')" wire:model="userData.name" icon="o-user" class="rounded-2xl" />
            <x-mary-input :label="__('user.fields.email')" type="email" wire:model="userData.email" icon="o-envelope" class="rounded-2xl" />
            
            <x-mary-input :label="__('user.student.nisn')" wire:model="userData.national_identifier" class="rounded-2xl" />
            <x-mary-input :label="__('user.student.nis')" wire:model="userData.registration_number" class="rounded-2xl" />
            
            <div class="md:col-span-2">
                <x-mary-select 
                    :label="__('user.student.department')" 
                    wire:model="userData.department_id" 
                    :options="$this->departments" 
                    placeholder="Select Department" 
                    class="rounded-2xl" 
                />
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button :label="__('common.actions.cancel')" @click="$wire.userModal = false" class="btn-ghost font-bold uppercase tracking-widest text-[10px]" />
            <x-mary-button :label="__('user.student.save')" class="btn-primary px-8 rounded-2xl font-black uppercase tracking-widest shadow-lg shadow-primary/20" wire:click="saveUser" spinner="saveUser" />
        </x-slot:actions>
    </x-mary-modal>
</div>
