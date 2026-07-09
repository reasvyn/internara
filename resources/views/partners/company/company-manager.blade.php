<x-core::ui.record-manager
    :title="__('company.title')"
    :subtitle="__('company.subtitle')"

>
    <x-slot:headerActions>
        <x-mary-button :label="__('company.add')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
    </x-slot:headerActions>

    <x-slot:extraMenu>
        <x-mary-menu-item :title="__('common.actions.import')" icon="o-arrow-up-tray" x-on:click="$refs.importCsvInput.click()" />
        <input x-ref="importCsvInput" type="file" accept=".csv" wire:model="importFile" class="hidden" />
        <x-mary-menu-item :title="__('common.actions.export')" icon="o-arrow-down-tray" wire:click="export" />
        <x-mary-menu-item :title="__('common.actions.template')" icon="o-document-arrow-down" wire:click="downloadTemplate" />
    </x-slot:extraMenu>

    <x-slot:stats>
        <x-core::widgets.stat-card icon="o-building-office" :title="__('company.stats.total')" :value="$this->stats['total']" />
        <x-core::widgets.stat-card icon="o-link" :title="__('company.stats.with_placements')" :value="$this->stats['with_placements']" />
        <x-core::widgets.stat-card icon="o-hand-raised" :title="__('company.stats.active_partnerships')" :value="$this->stats['active_partnerships']" />
        <x-core::widgets.stat-card icon="o-briefcase" :title="__('company.stats.available_slots')" :value="$this->stats['available_slots']" />
    </x-slot:stats>

    <x-slot:filters>
        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('company.industry_sector') }}</label>
        <input wire:model.live="filters.industry_sector" type="text" placeholder="{{ __('company.industry_sector_placeholder') }}" class="input input-bordered input-sm w-full text-sm" />

        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('company.phone') }}</label>
        <input wire:model.live="filters.phone" type="text" placeholder="{{ __('company.phone_placeholder') }}" class="input input-bordered input-sm w-full text-sm" />

        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('company.placements') }}</label>
        <select wire:model.live="filters.has_placements" class="select select-bordered select-sm w-full text-sm">
            <option value="">{{ __('common.actions.all') }}</option>
            <option value="yes">{{ __('common.yes') }}</option>
            <option value="no">{{ __('common.no') }}</option>
        </select>
    </x-slot:filters>

    <x-core::ui.selection-bar>
        <x-mary-dropdown>
            <x-slot:trigger>
                <x-mary-button icon="o-chevron-down" class="btn-sm btn-primary font-medium" :label="__('common.actions.bulk_actions')" />
            </x-slot:trigger>
            <div class="p-1.5 w-48">
                <x-mary-menu-item :title="__('common.actions.export_selected')" icon="o-arrow-down-tray" wire:click="exportSelected" />
                <hr class="border-base-content/10" />
                <x-mary-menu-item :title="__('common.actions.delete_selected')" icon="o-trash" class="text-error" wire:click="askDeleteSelected" />
            </div>
        </x-mary-dropdown>
    </x-core::ui.selection-bar>

    <x-core::ui.confirm
        wire:model="showConfirm"
        :message="$confirmMessage"
        confirmText="{{ __('common.actions.confirm') }}"
        cancelText="{{ __('common.actions.cancel') }}"
        confirmClass="btn-error"
    />

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
        <x-mary-modal wire:model="showModal" :title="$form->id ? __('company.edit') : __('company.new')" separator class="backdrop-blur-sm">
            <div class="space-y-5">
                <div class="bg-base-200/30 border border-base-content/10 rounded-xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-4">{{ __('company.identity') }}</p>
                    <x-mary-input :label="__('company.name')" wire:model="form.name" :placeholder="__('company.name_placeholder')" icon="o-building-office" />
                    <x-mary-input :label="__('company.industry_sector')" wire:model="form.industry_sector" :placeholder="__('company.industry_sector_placeholder')" icon="o-rectangle-stack" />
                    <x-mary-textarea :label="__('company.description')" wire:model="form.description" :placeholder="__('company.description_placeholder')" rows="2" icon="o-document-text" />
                </div>

                <div class="bg-base-200/30 border border-base-content/10 rounded-xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-4">{{ __('company.contact') }}</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-mary-input :label="__('company.email')" wire:model="form.email" :placeholder="__('company.email_placeholder')" icon="o-envelope" />
                        <x-mary-input :label="__('company.phone')" wire:model="form.phone" :placeholder="__('company.phone_placeholder')" icon="o-phone" />
                        <x-mary-input :label="__('company.website')" wire:model="form.website" :placeholder="__('company.website_placeholder')" class="md:col-span-2" icon="o-globe-alt" />
                    </div>
                </div>

                <div class="bg-base-200/30 border border-base-content/10 rounded-xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-4">{{ __('company.address') }}</p>
                    <x-mary-textarea :label="__('company.address')" wire:model="form.address" :placeholder="__('company.address_placeholder')" rows="2" icon="o-map-pin" />
                </div>
            </div>
            <x-slot:actions>
                <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showModal', false)" class="btn-ghost btn-sm" />
                <x-mary-button :label="__('company.save')" class="btn-primary btn-sm" wire:click="save" spinner="save" />
            </x-slot:actions>
        </x-mary-modal>
    </x-slot:modal>
    @include('partners.company.components.company-guide')
</x-core::ui.record-manager>
