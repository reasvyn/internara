<div class="p-8">
    {{-- Header Section --}}
    <x-mary-header :title="__('company.title')" :subtitle="__('company.subtitle')" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button :label="__('company.add')" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-mary-header>

    {{-- Stats Header --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <x-mary-stat :value="$this->stats['total']" :title="__('company.stats.total')" icon="o-building-office-2" class="rounded-[2rem] bg-base-100 border border-base-200" />
        <x-mary-stat :value="$this->stats['with_placements']" :title="__('company.stats.with_placements')" icon="o-briefcase" class="rounded-[2rem] bg-base-100 border border-base-200" />
        <x-mary-stat :value="$this->stats['available_slots']" :title="__('company.stats.available_slots')" icon="o-user-plus" class="rounded-[2rem] bg-base-100 border border-base-200" />
    </div>

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
                        wire:confirm="Delete selected companies? Only companies without active placements will be deleted."
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
                @scope('cell_name', $company)
                    <div class="flex flex-col">
                        <span class="font-bold text-sm">{{ $company->name }}</span>
                        @if($company->email)
                            <span class="text-xs opacity-50">{{ $company->email }}</span>
                        @endif
                    </div>
                @endscope

                @scope('cell_industry_sector', $company)
                    <x-mary-badge :value="$company->industry_sector ?? '-'" class="badge-ghost font-medium" />
                @endscope

                @scope('cell_address', $company)
                    <span class="text-xs opacity-70 line-clamp-1">{{ $company->address }}</span>
                @endscope

                @scope('actions', $company)
                    <div class="flex justify-end gap-1">
                        <x-mary-button icon="o-pencil" class="btn-ghost btn-sm text-primary" wire:click="edit('{{ $company->id }}')" />
                        <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error"
                            wire:confirm="{{ __('company.delete_confirm') }}"
                            wire:click="delete('{{ $company->id }}')" />
                    </div>
                @endscope
            </x-mary-table>
        </div>
    </x-mary-card>

    {{-- Form Modal --}}
    <x-mary-modal wire:model="showModal" :title="$formData['id'] ? __('company.edit') : __('company.new')" separator class="backdrop-blur-sm">
        <div class="space-y-6">
            <x-mary-input label="Company Name" wire:model="formData.name" icon="o-building-office" class="rounded-xl border-base-300" />
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-input label="Industry Sector" wire:model="formData.industry_sector" icon="o-tag" class="rounded-xl border-base-300" />
                <x-mary-input label="Email" wire:model="formData.email" icon="o-envelope" class="rounded-xl border-base-300" />
                <x-mary-input label="Phone" wire:model="formData.phone" icon="o-phone" class="rounded-xl border-base-300" />
                <x-mary-input label="Website" wire:model="formData.website" icon="o-globe-alt" class="rounded-xl border-base-300" />
            </div>

            <x-mary-textarea label="Address" wire:model="formData.address" rows="2" icon="o-map-pin" class="rounded-xl border-base-300" />
            <x-mary-textarea label="Description" wire:model="formData.description" rows="3" icon="o-document-text" class="rounded-xl border-base-300" />
        </div>

        <x-slot:actions>
            <x-mary-button :label="__('company.cancel')" @click="$wire.showModal = false" class="rounded-xl" />
            <x-mary-button :label="__('company.save')" class="btn-primary rounded-xl font-bold uppercase tracking-widest" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-mary-modal>
</div>
