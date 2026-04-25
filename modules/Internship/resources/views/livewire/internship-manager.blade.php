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
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <x-ui::select 
                        :label="__('internship::ui.filter_year')"
                        icon="tabler.calendar-stats"
                        wire:model.live="filters.academic_year" 
                        :options="[]" {{-- Future: Populate with real years --}}
                        :placeholder="__('internship::ui.all_years')"
                    />
                    <x-ui::select 
                        :label="__('internship::ui.filter_semester')"
                        icon="tabler.timeline"
                        wire:model.live="filters.semester" 
                        :options="$this->getSemesterOptions()" 
                        :placeholder="__('internship::ui.all_semesters')"
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
        <x-ui::input :label="__('internship::ui.title')" icon="tabler.presentation" wire:model="form.title" required />
        <x-ui::textarea :label="__('ui::common.description')" icon="tabler.align-left" wire:model="form.description" />
        
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-ui::input :label="__('internship::ui.academic_year')" icon="tabler.calendar-event" wire:model="form.academic_year" placeholder="e.g. 2025/2026" required />
            <x-ui::select 
                :label="__('internship::ui.semester')" 
                icon="tabler.timeline"
                wire:model="form.semester" 
                :placeholder="__('internship::ui.select_semester')"
                :options="$this->getSemesterOptions()" 
                required 
            />
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-ui::input :label="__('internship::ui.date_start')" icon="tabler.calendar" type="date" wire:model="form.date_start" required />
            <x-ui::input :label="__('internship::ui.date_finish')" icon="tabler.calendar" type="date" wire:model="form.date_finish" required />
        </div>
    </x-slot>

    {{-- 2. Import Instructions --}}
    <x-slot:importInstructions>
        Format CSV: Title, Description, Academic Year, Semester, Start Date (YYYY-MM-DD), Finish Date (YYYY-MM-DD)
    </x-slot>
</x-ui::record-manager>
