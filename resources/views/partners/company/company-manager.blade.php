<x-ui::components.record-manager :title="__('company.title')" :subtitle="__('company.subtitle')">
    <x-slot:headerActions>
        <x-ts-button :text="__('company.add')" icon="plus" color="primary" sm wire:click="create" />
    </x-slot:headerActions>

    <x-slot:extraMenu>
        <x-ts-dropdown.items :text="__('common.actions.import')" x-on:click="$refs.importCsvInput.click()" />
        <input x-ref="importCsvInput" type="file" accept=".csv" wire:model="importFile" class="hidden" />
        <x-ts-dropdown.items :text="__('common.actions.export')" icon="arrow-down-tray" wire:click="export" />
        <x-ts-dropdown.items
            :text="__('common.actions.template')"
            icon="document-arrow-down"
            wire:click="downloadTemplate"
        />
    </x-slot:extraMenu>

    <x-slot:stats>
        <x-ui::widgets.stat-card
            icon="building-office"
            :title="__('company.stats.total')"
            :value="$this->stats['total']"
        />
        <x-ui::widgets.stat-card
            icon="link"
            :title="__('company.stats.with_placements')"
            :value="$this->stats['with_placements']"
        />
        <x-ui::widgets.stat-card
            icon="hand-raised"
            :title="__('company.stats.active_partnerships')"
            :value="$this->stats['active_partnerships']"
        />
        <x-ui::widgets.stat-card
            icon="briefcase"
            :title="__('company.stats.available_slots')"
            :value="$this->stats['available_slots']"
        />
    </x-slot:stats>

    <x-slot:filters>
        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('company.industry_sector') }}</label>
        <input
            wire:model.live="filters.industry_sector"
            type="text"
            placeholder="{{ __('company.industry_sector_placeholder') }}"
            class="input input-bordered input-sm w-full text-sm"
        />

        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('company.phone') }}</label>
        <input
            wire:model.live="filters.phone"
            type="text"
            placeholder="{{ __('company.phone_placeholder') }}"
            class="input input-bordered input-sm w-full text-sm"
        />

        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('company.placements') }}</label>
        <x-ts-select.native wire:model.live="filters.has_placements" class="w-full text-sm">
            <option value="">{{ __('common.actions.all') }}</option>
            <option value="yes">{{ __('common.yes') }}</option>
            <option value="no">{{ __('common.no') }}</option>
        </x-ts-select.native>
    </x-slot:filters>

    <x-ui::components.selection-bar>
        <x-ts-dropdown>
            <x-slot:action>
                <x-ts-button
                    icon="chevron-down"
                    class="font-medium"
                    color="primary"
                    sm
                    :text="__('common.actions.bulk_actions')"
                / x-on:click="show = ! show">
            </x-slot:action>
            <div class="w-48 p-1.5">
                <x-ts-dropdown.items
                    :text="__('common.actions.export_selected')"
                    icon="arrow-down-tray"
                    wire:click="exportSelected"
                />
                <hr class="border-base-content/10" />
                <x-ts-dropdown.items
                    :text="__('common.actions.delete_selected')"
                    icon="trash"
                    class="text-error"
                    wire:click="askDeleteSelected"
                />
            </div>
        </x-ts-dropdown>
    </x-ui::components.selection-bar>

    <div class="overflow-x-auto">
        <x-ts-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            selectable
            wire:model="selectedIds"
            class="table-sm"
        >
            @interact('column_name', $company)
                <div class="flex flex-col">
                    <span class="text-sm font-medium">{{ $company->name }}</span>
                    @if ($company->email)
                        <span class="text-base-content/50 text-xs">{{ $company->email }}</span>
                    @endif
                </div>
            @endinteract

            @interact('column_industry_sector', $company)
                <span class="text-base-content/60 text-sm">{{ $company->industry_sector ?? '—' }}</span>
            @endinteract

            @interact('column_address', $company)
                <span class="text-base-content/50 line-clamp-1 text-xs">{{ $company->address }}</span>
            @endinteract

            @interact('column_action', $company)
                <div class="flex justify-end gap-1">
                    <x-ts-button
                        icon="pencil"
                        color="white"
                        sm
                        wire:click="edit('{{ $company->id }}')"
                        :aria-label="__('common.actions.edit')"
                    />
                    <x-ts-button
                        icon="trash"
                        class="text-error"
                        color="white"
                        sm
                        wire:click="askDelete('{{ $company->id }}')"
                        :aria-label="__('common.actions.delete')"
                    />
                </div>
            @endinteract
        </x-ts-table>
    </div>

    <x-slot:modal>
        <x-ts-modal wire="showModal" :title="$form->id ? __('company.edit') : __('company.new')" separator blur>
            <div class="space-y-5">
                <div class="bg-base-200/30 border-base-content/10 rounded-xl border p-5">
                    <p class="text-base-content/50 mb-4 text-xs font-semibold tracking-wider uppercase">
                        {{ __('company.identity') }}
                    </p>
                    <x-ts-input
                        :label="__('company.name')"
                        wire:model="form.name"
                        :placeholder="__('company.name_placeholder')"
                        icon="building-office"
                    />
                    <x-ts-input
                        :label="__('company.industry_sector')"
                        wire:model="form.industry_sector"
                        :placeholder="__('company.industry_sector_placeholder')"
                        icon="rectangle-stack"
                    />
                    <x-ts-textarea
                        :label="__('company.description')"
                        wire:model="form.description"
                        :placeholder="__('company.description_placeholder')"
                        rows="2"
                    />
                </div>

                <div class="bg-base-200/30 border-base-content/10 rounded-xl border p-5">
                    <p class="text-base-content/50 mb-4 text-xs font-semibold tracking-wider uppercase">
                        {{ __('company.contact') }}
                    </p>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-ts-input
                            :label="__('company.email')"
                            wire:model="form.email"
                            :placeholder="__('company.email_placeholder')"
                            icon="envelope"
                        />
                        <x-ts-input
                            :label="__('company.phone')"
                            wire:model="form.phone"
                            :placeholder="__('company.phone_placeholder')"
                            icon="phone"
                        />
                        <x-ts-input
                            :label="__('company.website')"
                            wire:model="form.website"
                            :placeholder="__('company.website_placeholder')"
                            class="md:col-span-2"
                            icon="globe-alt"
                        />
                    </div>
                </div>

                <div class="bg-base-200/30 border-base-content/10 rounded-xl border p-5">
                    <p class="text-base-content/50 mb-4 text-xs font-semibold tracking-wider uppercase">
                        {{ __('company.address') }}
                    </p>
                    <x-ts-textarea
                        :label="__('company.address')"
                        wire:model="form.address"
                        :placeholder="__('company.address_placeholder')"
                        rows="2"
                    />
                </div>
            </div>
            <x-slot:actions>
                <x-ts-button
                    :text="__('common.actions.cancel')"
                    wire:click="$set('showModal', false)"
                    color="white"
                    sm
                />
                <x-ts-button :text="__('company.save')" color="primary" sm wire:click="save" loading="save" />
            </x-slot:actions>
        </x-ts-modal>
    </x-slot:modal>
    @include('partners.company.components.company-guide')
</x-ui::components.record-manager>
