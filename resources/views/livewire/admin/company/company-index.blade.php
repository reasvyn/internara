<div class="p-8">
    <x-mary-header :title="__('company.title')" :subtitle="__('company.subtitle')" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button :label="__('company.add_company')" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-mary-header>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <x-mary-stat :value="$this->stats['total']" :title="__('company.stats.total')" icon="o-building-office" class="bg-base-100 border border-base-200" />
        <x-mary-stat :value="$this->stats['with_placements']" :title="__('company.stats.with_placements')" icon="o-briefcase" class="bg-base-100 border border-base-200" />
        <x-mary-stat :value="$this->stats['available_slots']" :title="__('company.stats.available_slots')" icon="o-user-group" class="bg-base-100 border border-base-200" />
    </div>

    <x-mary-card shadow class="bg-base-100 border border-base-200">
        <div class="mb-6 flex justify-between gap-4">
            <div class="w-full max-w-sm">
                <x-mary-input wire:model.live.debounce.300ms="search" :placeholder="__('company.search_placeholder')" icon="o-magnifying-glass" clearable />
            </div>
        </div>

        @php
            $headers = [
                ['key' => 'name', 'label' => __('company.company_name')],
                ['key' => 'industry_sector', 'label' => __('company.industry_sector')],
                ['key' => 'address', 'label' => __('company.address')],
                ['key' => 'actions', 'label' => '', 'sortable' => false]
            ];
        @endphp

        <x-mary-table :headers="$headers" :rows="$companies" with-pagination>
            @scope('cell_address', $company)
                <span class="text-sm text-base-content/70">
                    {{ Str::limit($company->address ?? '-', 40) }}
                </span>
            @endscope

            @scope('cell_industry_sector', $company)
                <x-mary-badge :value="$company->industry_sector ?: __('company.general_sector')" class="badge-neutral" />
            @endscope

            @scope('actions', $company)
                <div class="flex justify-end gap-2">
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm text-primary" wire:click="edit('{{ $company->id }}')" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error"
                        wire:confirm="{{ __('company.delete_confirm') }}"
                        wire:click="delete('{{ $company->id }}')" />
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    {{-- Form Modal --}}
    <x-mary-modal wire:model="showModal" :title="$companyId ? __('company.edit_company') : __('company.new_company')" separator class="backdrop-blur-sm">
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-input :label="__('company.name')" wire:model="name" :placeholder="__('company.name_placeholder')" icon="o-building-office" />
                <x-mary-input :label="__('company.industry_sector')" wire:model="industry_sector" :placeholder="__('company.industry_sector_placeholder')" icon="o-tag" />

                <div class="md:col-span-2">
                    <x-mary-textarea :label="__('company.address')" wire:model="address" :placeholder="__('company.address_placeholder')" rows="2" icon="o-map-pin" />
                </div>

                <x-mary-input :label="__('company.phone')" wire:model="phone" :placeholder="__('company.phone_placeholder')" icon="o-phone" />
                <x-mary-input :label="__('company.email')" type="email" wire:model="email" :placeholder="__('company.email_placeholder')" icon="o-envelope" />

                <div class="md:col-span-2">
                    <x-mary-input :label="__('company.website')" type="url" wire:model="website" :placeholder="__('company.website_placeholder')" icon="o-globe-alt" />
                </div>

                <div class="md:col-span-2">
                    <x-mary-textarea :label="__('company.description')" wire:model="description" :placeholder="__('company.description_placeholder')" rows="2" icon="o-document-text" />
                </div>
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button :label="__('company.cancel')" @click="$wire.showModal = false" />
            <x-mary-button :label="__('company.save')" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-mary-modal>
</div>
