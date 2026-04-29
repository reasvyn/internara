<div class="p-8">
    <x-mary-header title="Companies / Industry Partners" subtitle="Manage DU/DI for internship placements" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button label="Add Company" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-mary-header>

    <x-mary-card shadow class="bg-base-100 border border-base-200">
        <div class="mb-6 flex justify-between gap-4">
            <div class="w-full max-w-sm">
                <x-mary-input wire:model.live.debounce.300ms="search" placeholder="Search by name..." icon="o-magnifying-glass" clearable />
            </div>
        </div>

        @php
            $headers = [
                ['key' => 'name', 'label' => 'Company Name'],
                ['key' => 'industry_sector', 'label' => 'Industry Sector'],
                ['key' => 'address', 'label' => 'Address'],
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
                <x-mary-badge :value="$company->industry_sector ?: 'General'" class="badge-neutral" />
            @endscope

            @scope('actions', $company)
                <div class="flex justify-end gap-2">
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm text-primary" wire:click="edit('{{ $company->id }}')" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error" 
                        wire:confirm="Are you sure you want to delete this company?"
                        wire:click="delete('{{ $company->id }}')" />
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    {{-- Form Modal --}}
    <x-mary-modal wire:model="showModal" title="{{ $companyId ? 'Edit Company' : 'New Company' }}" separator class="backdrop-blur-sm">
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-input label="Company Name" wire:model="name" placeholder="e.g. PT Maju Mundur" />
                <x-mary-input label="Industry Sector" wire:model="industry_sector" placeholder="e.g. Technology" />
                
                <div class="md:col-span-2">
                    <x-mary-textarea label="Full Address" wire:model="address" rows="2" />
                </div>

                <x-mary-input label="Phone Number" wire:model="phone" />
                <x-mary-input label="Email Address" type="email" wire:model="email" />
                
                <div class="md:col-span-2">
                    <x-mary-input label="Website" type="url" wire:model="website" placeholder="https://..." />
                </div>
                
                <div class="md:col-span-2">
                    <x-mary-textarea label="Description / Notes" wire:model="description" rows="2" />
                </div>
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button label="Cancel" @click="$wire.showModal = false" />
            <x-mary-button label="Save" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-mary-modal>
</div>
