<div class="space-y-8">
    {{-- Executive Summary: Premium Stats Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        <x-ui::stat 
            :title="__('internship::ui.stats.total_partners')" 
            :value="$this->stats['total']" 
            icon="tabler.building-community" 
            variant="metadata" 
            class="stat-enterprise" 
        />
        <x-ui::stat 
            :title="__('internship::ui.stats.business_fields')" 
            :value="$this->stats['fields']" 
            icon="tabler.category" 
            variant="info" 
            class="stat-enterprise" 
        />
        <x-ui::stat 
            :title="__('internship::ui.stats.verified_contact')" 
            :value="$this->stats['with_email']" 
            icon="tabler.mail-check" 
            variant="success" 
            class="stat-enterprise" 
        />
        <x-ui::stat 
            :title="__('internship::ui.stats.new_partners')" 
            :value="$this->stats['latest']" 
            icon="tabler.sparkles" 
            variant="primary" 
            class="stat-enterprise" 
        />
    </div>

    <x-ui::record-manager>
        {{-- 1. Custom Filters (Dropdown Menu) --}}
        <x-slot:filters>
            <x-ui::dropdown :close-on-content-click="false" right>
                <x-slot:trigger>
                    <x-ui::button icon="tabler.filter" variant="secondary" class="gap-2">
                        <span>{{ __('internship::ui.filters_open') ?? __('ui::common.filters') }}</span>
                        @if($this->activeFilterCount() > 0)
                            <x-ui::badge :value="$this->activeFilterCount()" variant="info" class="badge-sm" />
                        @endif
                    </x-ui::button>
                </x-slot:trigger>

                <div class="w-[min(92vw,30rem)] space-y-4 p-2">
                    <div class="grid grid-cols-1 gap-3">
                        <x-ui::select
                            :label="__('internship::ui.business_field')"
                            icon="tabler.briefcase"
                            wire:model.live="filters.business_field"
                            :options="$this->businessFieldOptions()"
                            :placeholder="__('ui::common.all')"
                        />

                        <x-ui::input
                            :label="__('ui::common.email')"
                            icon="tabler.mail"
                            wire:model.live.debounce.300ms="filters.email"
                            :placeholder="__('ui::common.search_email')"
                            clearable
                        />
                    </div>

                    <div class="flex justify-end">
                        <x-ui::button
                            :label="__('internship::ui.filters_reset')"
                            icon="tabler.filter-off"
                            variant="secondary"
                            wire:click="resetFilters"
                        />
                    </div>
                </div>
            </x-ui::dropdown>
        </x-slot:filters>

        {{-- 2. Customized Table Cells --}}
        <x-slot:tableCells>
            @scope('cell_name', $company)
                <div class="flex flex-col min-w-[200px]">
                    <span class="font-bold text-sm text-base-content/90">{{ $company->name }}</span>
                    @if($company->address)
                        <span class="text-[10px] opacity-40 uppercase tracking-widest font-black line-clamp-1">{{ $company->address }}</span>
                    @endif
                </div>
            @endscope

            @scope('cell_business_field', $company)
                <x-ui::badge 
                    :value="$company->business_field ?? '-'" 
                    variant="neutral" 
                    class="badge-sm font-black text-[9px] uppercase tracking-tighter" 
                />
            @endscope
        </x-slot:tableCells>

        {{-- Row Actions --}}
        <x-slot:rowActions>
            @scope('cell_actions', $company)
                <div class="flex items-center justify-end gap-1 px-2">
                    @if($this->can('update', $company))
                        <x-ui::button icon="tabler.edit" variant="tertiary" class="text-info/40 hover:text-info btn-xs" wire:click="edit('{{ $company->id }}')" tooltip="{{ __('ui::common.edit') }}" />
                    @endif
                    @if($this->can('delete', $company))
                        <x-ui::button icon="tabler.trash" variant="tertiary" class="text-error/40 hover:text-error btn-xs" wire:click="discard('{{ $company->id }}')" tooltip="{{ __('ui::common.delete') }}" />
                    @endif
                </div>
            @endscope
        </x-slot:rowActions>

        {{-- 3. Form Fields --}}
        <x-slot:formFields>
            <x-ui::input
                :label="__('internship::ui.company_name')"
                icon="tabler.building"
                wire:model="form.name"
                required
            />

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui::input
                    :label="__('internship::ui.business_field')"
                    icon="tabler.briefcase"
                    wire:model="form.business_field"
                />
                <x-ui::input
                    :label="__('internship::ui.leader_name')"
                    icon="tabler.user"
                    wire:model="form.leader_name"
                />
            </div>

            <x-ui::textarea
                :label="__('internship::ui.company_address')"
                icon="tabler.map-pin"
                wire:model="form.address"
                rows="2"
            />

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui::input
                    :label="__('internship::ui.company_phone')"
                    icon="tabler.phone"
                    wire:model="form.phone"
                />
                <x-ui::input
                    :label="__('internship::ui.company_fax')"
                    icon="tabler.phone-off"
                    wire:model="form.fax"
                />
            </div>

            <x-ui::input
                :label="__('ui::common.email')"
                icon="tabler.mail"
                type="email"
                wire:model="form.email"
            />
        </x-slot:formFields>

        {{-- 4. Import Instructions --}}
        <x-slot:importInstructions>
            {{ __('internship::ui.company_import_format') }}
        </x-slot:importInstructions>
    </x-ui::record-manager>
</div>
