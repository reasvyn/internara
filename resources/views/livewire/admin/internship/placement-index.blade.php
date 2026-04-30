<div class="p-8">
    <x-mary-header :title="__('placement.title')" :subtitle="__('placement.subtitle')" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button :label="__('placement.add_placement')" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-mary-header>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <x-mary-stat :value="$this->stats['total']" :title="__('placement.stats.total')" icon="o-briefcase" class="bg-base-100 border border-base-200" />
        <x-mary-stat :value="$this->stats['total_quota']" :title="__('placement.stats.total_quota')" icon="o-users" class="bg-base-100 border border-base-200" />
        <x-mary-stat :value="$this->stats['filled']" :title="__('placement.stats.filled')" icon="o-user" class="bg-base-100 border border-base-200" />
        <x-mary-stat :value="$this->stats['available']" :title="__('placement.stats.available')" icon="o-users" class="bg-base-100 border border-base-200" />
    </div>

    <x-mary-card shadow class="bg-base-100 border border-base-200">
        <div class="mb-6 flex justify-between gap-4">
            <div class="w-full max-w-sm">
                <x-mary-input wire:model.live.debounce.300ms="search" :placeholder="__('placement.search_placeholder')" icon="o-magnifying-glass" clearable />
            </div>
        </div>

        @php
            $headers = [
                ['key' => 'name', 'label' => __('placement.position_name')],
                ['key' => 'company.name', 'label' => __('placement.company_name')],
                ['key' => 'internship.name', 'label' => __('placement.batch')],
                ['key' => 'quota_info', 'label' => __('placement.quota_info')],
                ['key' => 'actions', 'label' => '', 'sortable' => false]
            ];
        @endphp

        <x-mary-table :headers="$headers" :rows="$placements" with-pagination>
            @scope('cell_quota_info', $placement)
                <div class="flex items-center gap-2">
                    <progress class="progress {{ $placement->isFull() ? 'progress-error' : 'progress-primary' }} w-16" value="{{ $placement->filled_quota }}" max="{{ $placement->quota }}"></progress>
                    <span class="text-xs text-base-content/70 whitespace-nowrap">{{ $placement->filled_quota }} / {{ $placement->quota }}</span>
                </div>
            @endscope

            @scope('actions', $placement)
                <div class="flex justify-end gap-2">
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm text-primary" wire:click="edit('{{ $placement->id }}')" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error"
                        wire:confirm="{{ __('placement.delete_confirm') }}"
                        wire:click="delete('{{ $placement->id }}')" />
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>

    {{-- Form Modal --}}
    <x-mary-modal wire:model="showModal" :title="$this->placementId ? __('placement.edit_placement') : __('placement.new_placement')" separator class="backdrop-blur-sm">
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-mary-select :label="__('placement.company')" wire:model="company_id" :options="$this->companies" :placeholder="__('placement.company_placeholder')" icon="o-building-office" />
                <x-mary-select :label="__('placement.internship')" wire:model="internship_id" :options="$this->internships" :placeholder="__('placement.internship_placeholder')" icon="o-calendar" />

                <div class="md:col-span-2">
                    <x-mary-input :label="__('placement.name')" wire:model="name" :placeholder="__('placement.name_placeholder')" icon="o-briefcase" />
                </div>

                <x-mary-input :label="__('placement.quota_label')" type="number" wire:model="quota" icon="o-user-group" />
                <x-mary-textarea :label="__('placement.address')" wire:model="address" :placeholder="__('placement.address_placeholder')" rows="2" icon="o-map-pin" />

                <div class="md:col-span-2">
                    <x-mary-textarea :label="__('placement.description')" wire:model="description" :placeholder="__('placement.description_placeholder')" rows="3" icon="o-document-text" />
                </div>
            </div>
        </div>

        <x-slot:actions>
            <x-mary-button :label="__('placement.cancel')" @click="$wire.showModal = false" />
            <x-mary-button :label="__('placement.save')" class="btn-primary" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-mary-modal>
</div>
