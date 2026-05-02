<div class="p-8">
    <x-layouts.manager 
        :title="__('company.title')" 
        :subtitle="__('company.subtitle')" 
        :rows="$this->rows()" 
        :headers="$this->headers()"
        :selected-count="$this->selected_count"
        :sort-by="$sortBy"
    >
        {{-- Stats Header --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <x-mary-stat :value="$this->stats['total']" :title="__('company.stats.total')" icon="o-building-office-2" class="rounded-[2rem] bg-base-100 border border-base-200" />
            <x-mary-stat :value="$this->stats['with_placements']" :title="__('company.stats.with_placements')" icon="o-briefcase" class="rounded-[2rem] bg-base-100 border border-base-200" />
            <x-mary-stat :value="$this->stats['available_slots']" :title="__('company.stats.available_slots')" icon="o-user-plus" class="rounded-[2rem] bg-base-100 border border-base-200" />
        </div>

        {{-- Top Actions --}}
        <x-slot:actions>
            <x-mary-button :label="__('company.add')" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>

        {{-- Bulk Actions --}}
        <x-slot:bulkActions>
            <x-mary-button 
                label="Delete Selected" 
                icon="o-trash" 
                class="btn-sm btn-error text-white font-bold rounded-lg" 
                wire:confirm="Delete selected companies? Only companies without active placements will be deleted."
                wire:click="deleteSelected" 
            />
        </x-slot:bulkActions>

        {{-- Table Cell Overrides --}}
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
    </x-layouts.manager>

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
