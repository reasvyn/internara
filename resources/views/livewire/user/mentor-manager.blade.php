<div class="animate-in fade-in slide-in-from-bottom-8 duration-1000">
    {{-- Header Section --}}
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-8 gap-4">
        <div>
            <h2 class="text-3xl font-black tracking-tightest text-base-content">{{ __('user.mentor.title') }}</h2>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-base-content/40 mt-2">{{ __('user.mentor.subtitle') }}</p>
        </div>
        <x-mary-button :label="__('user.mentor.new')" icon="o-plus" class="btn-primary rounded-[2rem] font-black uppercase tracking-[0.2em] text-[10px] px-8 h-12 shadow-2xl shadow-primary/30 hover:scale-[1.02] transition-transform" wire:click="create" />
    </div>

    {{-- Controls Section --}}
    <div class="mb-8 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
        <div class="w-full lg:max-w-md relative group">
            <div class="absolute inset-0 bg-primary/5 rounded-[1.5rem] blur-md transition-opacity duration-300 opacity-0 group-focus-within:opacity-100"></div>
            <x-mary-input 
                wire:model.live.debounce.300ms="search" 
                placeholder="{{ __('Search records...') }}" 
                icon="o-magnifying-glass" 
                clearable 
                class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 transition-all duration-300 bg-base-200/50 focus:bg-base-100 h-14 relative z-10"
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
                <div class="flex gap-2">
                    <x-mary-button 
                        :label="__('common.actions.delete_selected')" 
                        icon="o-trash" 
                        class="btn-error text-white font-black uppercase tracking-widest text-[10px] rounded-xl h-10 px-6 shadow-lg shadow-error/20 hover:scale-105 transition-transform" 
                        :wire:confirm="__('common.actions.confirm_action')"
                        wire:click="deleteSelected" 
                    />
                </div>
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

                @scope('actions', $user)
                    <div class="flex items-center justify-end gap-2 py-2">
                        <x-mary-button icon="o-pencil" class="btn-ghost btn-sm btn-circle text-primary hover:bg-primary/10 transition-colors" wire:click="edit('{{ $user->id }}')" tooltip="Edit" />
                        <x-mary-button icon="o-trash" class="btn-ghost btn-sm btn-circle text-error hover:bg-error/10 transition-colors" wire:confirm="{{ __('common.actions.confirm_action') }}" wire:click="delete('{{ $user->id }}')" tooltip="Delete" />
                    </div>
                @endscope
            </x-mary-table>
        </div>
    </x-mary-card>

    {{-- Mentor Modal --}}
    <x-mary-modal wire:model="userModal" :title="$userData['id'] ? __('user.mentor.edit') : __('user.mentor.new')" class="backdrop-blur-sm" box-class="rounded-[2.5rem] p-6 border border-base-content/5 shadow-2xl">
        <div class="grid grid-cols-1 gap-6 pt-4">
            <x-mary-input :label="__('user.fields.full_name')" wire:model="userData.name" icon="o-user" class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 bg-base-200/50 py-3" />
            <x-mary-input :label="__('user.fields.email')" type="email" wire:model="userData.email" icon="o-envelope" class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 bg-base-200/50 py-3" />
            <x-mary-input :label="__('user.mentor.phone')" wire:model="userData.phone" icon="o-phone" class="rounded-[1.5rem] border-base-content/5 focus:border-primary/30 bg-base-200/50 py-3" />
        </div>

        <x-slot:actions>
            <div class="flex gap-4 pt-6 border-t border-base-content/5 w-full justify-end">
                <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('userModal', false)" class="btn-ghost rounded-[1.5rem] font-black uppercase tracking-widest text-[10px] px-8" />
                <x-mary-button :label="__('user.mentor.save')" type="submit" class="btn-primary rounded-[1.5rem] font-black uppercase tracking-[0.2em] text-[10px] px-10 shadow-xl shadow-primary/20" wire:click="save" spinner="save" />
            </div>
        </x-slot:actions>
    </x-mary-modal>
</div>
