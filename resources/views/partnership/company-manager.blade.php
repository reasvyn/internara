<x-shared::ui.record-manager
    :title="__('company.title')"
    :subtitle="__('company.subtitle')"

>
    <x-slot:headerActions>
        <x-mary-button :label="__('company.add')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
    </x-slot:headerActions>

    <x-slot:extraMenu>
        <x-mary-menu-item :title="__('common.actions.import')" icon="o-arrow-up-tray" onclick="document.getElementById('import-csv').click()" />
        <input id="import-csv" type="file" accept=".csv" wire:model="importFile" class="hidden" />
        <x-mary-menu-item :title="__('common.actions.export')" icon="o-arrow-down-tray" wire:click="export" />
        <x-mary-menu-item :title="__('common.actions.template')" icon="o-document-arrow-down" wire:click="downloadTemplate" />
    </x-slot:extraMenu>

    <x-slot:stats>
        <x-mary-card class="bg-base-200/40 border border-base-content/10 p-4 text-center">
            <p class="text-2xl font-bold">{{ $this->stats['total'] }}</p>
            <p class="text-xs text-base-content/50 mt-1">{{ __('company.stats.total') }}</p>
        </x-mary-card>
        <x-mary-card class="bg-base-200/40 border border-base-content/10 p-4 text-center">
            <p class="text-2xl font-bold">{{ $this->stats['with_placements'] }}</p>
            <p class="text-xs text-base-content/50 mt-1">{{ __('company.stats.with_placements') }}</p>
        </x-mary-card>
        <x-mary-card class="bg-base-200/40 border border-base-content/10 p-4 text-center">
            <p class="text-2xl font-bold">{{ $this->stats['available_slots'] }}</p>
            <p class="text-xs text-base-content/50 mt-1">{{ __('company.stats.available_slots') }}</p>
        </x-mary-card>
    </x-slot:stats>

    <x-slot:filters>
        <x-mary-input
            wire:model.live="filters.industry_sector"
            :placeholder="__('company.industry_sector')"
            icon="o-magnifying-glass"
            clearable
            class="sm:max-w-xs"
        />
    </x-slot:filters>

    <x-shared::ui.selection-bar>
            <x-mary-dropdown>
            <x-slot:trigger>
                <x-mary-button icon="o-chevron-down" class="btn-sm btn-primary font-medium" :label="__('common.actions.bulk_actions')" />
            </x-slot:trigger>
            <div class="p-1.5 w-48">
                <x-mary-menu-item title="Delete Selected" icon="o-trash" class="text-error"
                    wire:click="askDeleteSelected" />
            </div>
        </x-mary-dropdown>
    </x-shared::ui.selection-bar>

    <x-shared::ui.confirm
        wire:model="showConfirm"
        :message="$confirmMessage"
        confirmText="{{ __('common.actions.confirm') }}"
        cancelText="{{ __('common.actions.cancel') }}"
        confirmClass="btn-error"
    />

    <div class="overflow-x-auto">

    <div class="overflow-x-auto">
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
                    <span class="font-medium text-sm">{{ $company->name }}</span>
                    @if($company->email)
                        <span class="text-xs text-base-content/50">{{ $company->email }}</span>
                    @endif
                </div>
            @endscope

            @scope('cell_industry_sector', $company)
                <span class="text-sm text-base-content/60">{{ $company->industry_sector ?? '—' }}</span>
            @endscope

            @scope('cell_address', $company)
                <span class="text-xs text-base-content/50 line-clamp-1">{{ $company->address }}</span>
            @endscope

            @scope('actions', $company)
                <div class="flex justify-end gap-1">
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="edit('{{ $company->id }}')" :aria-label="__('common.actions.edit')" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error"
                        wire:click="askDelete('{{ $company->id }}')"
                        :aria-label="__('common.actions.delete')" />
                </div>
            @endscope
        </x-mary-table>
    </div>

    <x-slot:modal>
        <x-mary-modal wire:model="showModal" :title="$form->id ? __('company.edit') : __('company.new')" class="backdrop-blur-sm">
            <x-mary-form wire:submit="save">
                <div class="space-y-5">
                    <x-mary-input :label="__('company.name')" wire:model="form.name" :placeholder="__('company.name_placeholder')" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-mary-input :label="__('company.industry_sector')" wire:model="form.industry_sector" :placeholder="__('company.industry_sector_placeholder')" />
                        <x-mary-input :label="__('company.email')" wire:model="form.email" :placeholder="__('company.email_placeholder')" />
                        <x-mary-input :label="__('company.phone')" wire:model="form.phone" :placeholder="__('company.phone_placeholder')" />
                        <x-mary-input :label="__('company.website')" wire:model="form.website" :placeholder="__('company.website_placeholder')" />
                    </div>
                    <x-mary-textarea :label="__('company.address')" wire:model="form.address" :placeholder="__('company.address_placeholder')" rows="2" />
                    <x-mary-textarea :label="__('company.description')" wire:model="form.description" :placeholder="__('company.description_placeholder')" rows="3" />
                </div>
                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('company.save')" class="btn-primary btn-sm" type="submit" spinner="save" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>
</x-shared::ui.record-manager>
