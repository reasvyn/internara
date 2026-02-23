<x-ui::record-manager>
    {{-- 1. Custom Filters --}}
    <x-slot:filters>
        <div class="flex items-center gap-3">
            <x-ui::select 
                icon="tabler.calendar-stats"
                wire:model.live="filters.academic_year" 
                :options="[]" {{-- Future: Populate with real years --}}
                placeholder="{{ __('internship::ui.filter_year') }}"
                class="w-48"
            />
            <x-ui::select 
                icon="tabler.timeline"
                wire:model.live="filters.semester" 
                :options="[
                    ['id' => 'Ganjil', 'name' => __('internship::ui.semester_odd')], 
                    ['id' => 'Genap', 'name' => __('internship::ui.semester_even')],
                    ['id' => 'Tahunan', 'name' => __('internship::ui.semester_full')]
                ]" 
                placeholder="{{ __('internship::ui.filter_semester') }}"
                class="w-48"
            />
        </div>
    </x-slot>

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
                :options="[
                    ['id' => 'Ganjil', 'name' => __('internship::ui.semester_odd')], 
                    ['id' => 'Genap', 'name' => __('internship::ui.semester_even')],
                    ['id' => 'Tahunan', 'name' => __('internship::ui.semester_full')]
                ]" 
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
