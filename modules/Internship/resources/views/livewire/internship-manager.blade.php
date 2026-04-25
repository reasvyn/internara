<div class="space-y-8">
    {{-- Executive Summary: Premium Stats Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        <x-ui::stat 
            :title="__('internship::ui.stats.total_programs')" 
            :value="$this->stats['total']" 
            icon="tabler.layers-intersect" 
            variant="metadata" 
            class="shadow-sm border border-base-content/5 bg-base-100/50" 
        />
        <x-ui::stat 
            :title="__('internship::ui.stats.open_registration')" 
            :value="$this->stats['active']" 
            icon="tabler.door-open" 
            variant="success" 
            class="shadow-sm border border-base-content/5 bg-base-100/50" 
        />
        <x-ui::stat 
            :title="__('internship::ui.stats.ongoing_programs')" 
            :value="$this->stats['ongoing']" 
            icon="tabler.activity" 
            variant="primary" 
            class="shadow-sm border border-base-content/5 bg-base-100/50" 
        />
        <x-ui::stat 
            :title="__('internship::ui.stats.upcoming_programs')" 
            :value="$this->stats['upcoming']" 
            icon="tabler.calendar-bolt" 
            variant="info" 
            class="shadow-sm border border-base-content/5 bg-base-100/50" 
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

        {{-- 2. Customized Table Cells --}}
        <x-slot:tableCells>
            @scope('cell_title', $internship)
                <div class="flex flex-col min-w-[200px]">
                    <span class="font-bold text-sm text-base-content/90">{{ $internship['title'] }}</span>
                    <span class="text-[10px] opacity-40 uppercase tracking-widest font-black line-clamp-1">{{ $internship['description'] ?: '-' }}</span>
                </div>
            @endscope

            @scope('cell_status', $internship)
                <x-ui::badge 
                    :value="$internship['status_label']" 
                    :variant="$internship['status_color']" 
                    class="badge-sm font-black text-[9px] uppercase tracking-tighter" 
                />
            @endscope
        </x-slot:tableCells>

        {{-- 3. Customized Row Actions --}}
        <x-slot:rowActions>
            @scope('actions', $internship)
                <div class="flex items-center justify-end gap-1">
                    {{-- Status Transition Menu --}}
                    <x-ui::dropdown icon="tabler.settings-automation" variant="tertiary" class="btn-xs text-primary" right>
                        <x-ui::menu-item title="internship::ui.status.publish" icon="tabler.broadcast" wire:click="updateStatus('{{ $internship['id'] }}', 'published')" />
                        <x-ui::menu-item title="internship::ui.status.open" icon="tabler.door-open" wire:click="updateStatus('{{ $internship['id'] }}', 'open')" />
                        <x-ui::menu-item title="internship::ui.status.ongoing" icon="tabler.activity" wire:click="updateStatus('{{ $internship['id'] }}', 'ongoing')" />
                        <x-ui::menu-item title="internship::ui.status.complete" icon="tabler.circle-check" wire:click="updateStatus('{{ $internship['id'] }}', 'completed')" />
                        <x-ui::menu-separator />
                        <x-ui::menu-item title="internship::ui.status.close" icon="tabler.door-off" class="text-warning" wire:click="updateStatus('{{ $internship['id'] }}', 'closed')" />
                        <x-ui::menu-item title="internship::ui.status.archive" icon="tabler.archive" class="text-error" wire:click="updateStatus('{{ $internship['id'] }}', 'archived')" />
                    </x-ui::dropdown>

                    <div class="divider divider-horizontal mx-0 opacity-10"></div>

                    @if($this->can('update'))
                        <x-ui::button icon="tabler.edit" variant="tertiary" class="text-info btn-xs" wire:click="edit('{{ $internship['id'] }}')" tooltip="{{ __('ui::common.edit') }}" />
                    @endif
                    @if($this->can('delete'))
                        <x-ui::button icon="tabler.trash" variant="tertiary" class="text-error btn-xs" wire:click="discard('{{ $internship['id'] }}')" tooltip="{{ __('ui::common.delete') }}" />
                    @endif
                </div>
            @endscope
        </x-slot:rowActions>

        {{-- 4. Form Fields --}}
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
        </x-slot:formFields>

        {{-- 5. Import Instructions --}}
        <x-slot:importInstructions>
            Format CSV: Title, Description, Academic Year, Semester, Start Date (YYYY-MM-DD), Finish Date (YYYY-MM-DD)
        </x-slot:importInstructions>
    </x-ui::record-manager>
</div>
