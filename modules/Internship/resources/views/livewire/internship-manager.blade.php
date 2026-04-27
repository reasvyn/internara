<div class="space-y-8">
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

                <div class="w-[min(92vw,30rem)] space-y-4 p-4 bg-base-100 rounded-2xl shadow-xl border border-base-content/5">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-ui::select 
                            :label="__('internship::ui.filter_year')"
                            icon="tabler.calendar-stats"
                            wire:model.live="filters.academic_year" 
                            :options="array_map(fn($y) => ['id' => $y, 'name' => $y], $this->academicYears)" 
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

                    <div class="flex justify-end gap-2 pt-2 border-t border-base-content/5">
                        <x-ui::button
                            :label="__('internship::ui.filters_reset')"
                            icon="tabler.filter-off"
                            variant="ghost"
                            class="text-xs uppercase font-black opacity-50 hover:opacity-100"
                            wire:click="resetFilters"
                        />
                    </div>
                </div>
            </x-ui::dropdown>
        </x-slot:filters>

        {{-- Bulk Actions --}}
        <x-slot:bulkActions>
            @if(count($this->selectedIds) > 0)
                <x-ui::dropdown :label="__('ui::common.bulk_actions')" icon="tabler.stack-2" variant="secondary" class="btn-sm rounded-xl">
                    <x-ui::menu-item title="internship::ui.status.open" icon="tabler.door-open" wire:click="bulkUpdateStatus('open')" />
                    <x-ui::menu-item title="internship::ui.status.ongoing" icon="tabler.activity" wire:click="bulkUpdateStatus('ongoing')" />
                    <x-ui::menu-item title="internship::ui.status.complete" icon="tabler.circle-check" wire:click="bulkUpdateStatus('completed')" />
                    <x-ui::menu-separator />
                    <x-ui::menu-item title="ui::common.delete" icon="tabler.trash" class="text-error" wire:click="removeSelected" />
                </x-ui::dropdown>
            @endif
        </x-slot:bulkActions>

        {{-- 2. Customized Table Cells --}}
        <x-slot:tableCells>
            @scope('cell_title', $internship)
                <div class="flex flex-col min-w-[200px] group">
                    <span class="font-bold text-sm text-base-content/90 group-hover:text-primary transition-colors">{{ $internship->title }}</span>
                    <span class="text-[10px] opacity-40 uppercase tracking-widest font-black line-clamp-1 italic">{{ $internship->description ?: '-' }}</span>
                </div>
            @endscope

            @scope('cell_status', $internship)
                <x-ui::badge 
                    :value="$internship->status_label" 
                    :variant="$internship->status_color" 
                    class="badge-sm font-black text-[9px] uppercase tracking-tighter rounded-lg" 
                />
            @endscope
        </x-slot:tableCells>

        {{-- 3. Customized Row Actions --}}
        <x-slot:rowActions>
            @scope('actions', $internship)
                <div class="flex items-center justify-end gap-1">
                    {{-- Status Transition Menu --}}
                    <x-ui::dropdown icon="tabler.settings-automation" variant="tertiary" class="btn-xs text-primary/40 hover:text-primary" right>
                        <x-ui::menu-item title="internship::ui.status.publish" icon="tabler.broadcast" wire:click="updateStatus('{{ $internship->id }}', 'published')" />
                        <x-ui::menu-item title="internship::ui.status.open" icon="tabler.door-open" wire:click="updateStatus('{{ $internship->id }}', 'open')" />
                        <x-ui::menu-item title="internship::ui.status.ongoing" icon="tabler.activity" wire:click="updateStatus('{{ $internship->id }}', 'ongoing')" />
                        <x-ui::menu-item title="internship::ui.status.complete" icon="tabler.circle-check" wire:click="updateStatus('{{ $internship->id }}', 'completed')" />
                        <x-ui::menu-separator />
                        <x-ui::menu-item title="internship::ui.status.close" icon="tabler.door-off" class="text-warning" wire:click="updateStatus('{{ $internship->id }}', 'closed')" />
                        <x-ui::menu-item title="internship::ui.status.archive" icon="tabler.archive" class="text-error" wire:click="updateStatus('{{ $internship->id }}', 'archived')" />
                    </x-ui::dropdown>

                    <div class="divider divider-horizontal mx-0 opacity-5"></div>

                    @if($this->can('update', $internship))
                        <x-ui::button icon="tabler.edit" variant="tertiary" class="text-info/40 hover:text-info btn-xs" wire:click="edit('{{ $internship->id }}')" tooltip="{{ __('ui::common.edit') }}" />
                    @endif
                    @if($this->can('delete', $internship))
                        <x-ui::button icon="tabler.trash" variant="tertiary" class="text-error/40 hover:text-error btn-xs" wire:click="discard('{{ $internship->id }}')" tooltip="{{ __('ui::common.delete') }}" />
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

            {{-- Loading State for Modal Actions --}}
            <div wire:loading wire:target="save" class="text-[10px] font-black uppercase tracking-widest text-primary animate-pulse mt-2">
                {{ __('ui::common.saving') }}...
            </div>
        </x-slot:formFields>

        {{-- 5. Import Instructions --}}
        <x-slot:importInstructions>
            <div class="text-[11px] opacity-70 leading-relaxed">
                <p class="font-bold mb-2 uppercase tracking-wider">{{ __('ui::common.import_instructions') }}</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>{{ __('internship::ui.import_csv_format') }}</li>
                    <li>{{ __('internship::ui.import_date_format') }}</li>
                    <li>{{ __('internship::ui.import_semester_values') }}</li>
                </ul>
            </div>
        </x-slot:importInstructions>
    </x-ui::record-manager>
</div>
