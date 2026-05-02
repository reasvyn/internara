<div class="p-8">
    <x-layouts.manager 
        :title="__('user.student.title')" 
        :subtitle="__('user.student.subtitle')" 
        :rows="$this->rows()" 
        :headers="$this->headers()"
        :selected-count="$this->selected_count"
        :sort-by="$sortBy"
    >
        {{-- Top Actions --}}
        <x-slot:actions>
            <x-mary-button :label="__('user.student.new')" icon="o-plus-circle" class="btn-primary rounded-2xl font-black uppercase tracking-widest px-6 shadow-lg shadow-primary/20" wire:click="createUser" />
        </x-slot:actions>

        {{-- Filters --}}
        <x-slot:filters>
            <x-mary-select 
                wire:model.live="filters.department_id" 
                :options="$this->departments" 
                :placeholder="__('user.student.department')" 
                clearable 
                class="rounded-2xl border-base-300 focus:border-primary shadow-sm"
            />
        </x-slot:filters>

        {{-- Bulk Actions --}}
        <x-slot:bulkActions>
            <x-mary-button 
                :label="__('common.actions.delete_selected')" 
                icon="o-trash" 
                class="btn-sm btn-error text-white font-black uppercase tracking-widest text-[10px] rounded-xl px-4 shadow-md" 
                :wire:confirm="__('common.actions.confirm_action')"
                wire:click="deleteSelected" 
            />
            <x-mary-button label="Export Data" icon="o-arrow-down-tray" class="btn-sm btn-ghost font-black uppercase tracking-widest text-[10px] rounded-xl" />
        </x-slot:bulkActions>

        {{-- Mass Actions --}}
        <x-slot:massActions>
            <x-mary-button label="Archive All Filtered" icon="o-archive-box" class="btn-ghost font-black uppercase tracking-widest text-[10px] text-warning rounded-xl border-warning/20" wire:click="archiveAllFiltered" />
        </x-slot:massActions>

        {{-- Table Cell Overrides --}}
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
    </x-layouts.manager>

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
