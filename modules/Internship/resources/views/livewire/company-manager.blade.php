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

    {{-- 2. Form Fields --}}
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

    {{-- 3. Import Instructions --}}
    <x-slot:importInstructions>
        {{ __('internship::ui.company_import_format') }}
    </x-slot:importInstructions>
</x-ui::record-manager>
