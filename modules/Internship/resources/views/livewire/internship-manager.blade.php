<div>
    <x-ui::header 
        wire:key="internship-manager-header"
        :title="__('internship::ui.program_title')" 
        :subtitle="__('internship::ui.program_subtitle')"
        :context="'internship::ui.index.title'"
    >
        <x-slot:actions wire:key="internship-manager-actions">
            <div class="flex items-center gap-3 relative z-50">
                <x-ui::dropdown icon="tabler.dots" variant="tertiary" right>
                    <x-ui::menu-item :title="__('ui::common.print')" icon="tabler.printer" wire:click="printPdf" />
                    <x-ui::menu-item :title="__('ui::common.export')" icon="tabler.download" wire:click="exportCsv" />
                    <x-ui::menu-item :title="__('ui::common.import')" icon="tabler.upload" wire:click="$set('importModal', true)" />
                </x-ui::dropdown>
                <x-ui::button :label="__('internship::ui.add_program')" icon="tabler.plus" variant="primary" wire:click="add" />
            </div>
        </x-slot:actions>
    </x-ui::header>

        <x-ui::card wire:key="internship-manager-card">
            <div 
                x-data="{ 
                    search: $wire.entangle('search', true),
                    applyFilter() {
                        let term = this.search.toLowerCase();
                        let rows = this.$el.querySelectorAll('table tbody tr:not(.mary-table-empty)');
                        rows.forEach(row => {
                            let text = row.innerText.toLowerCase();
                            row.style.display = text.includes(term) ? '' : 'none';
                        });
                    }
                }" 
                x-init="$watch('search', () => applyFilter())"
            >
                <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="w-full md:w-1/3">
                        <x-ui::input 
                            :placeholder="__('internship::ui.search_program')" 
                            icon="tabler.search" 
                            wire:model.live.debounce.250ms="search" 
                            x-model="search"
                            clearable 
                        />
                    </div>
                </div>
    
                                                    <div class="w-full overflow-auto rounded-xl border border-base-200 bg-base-100 shadow-sm max-h-[60vh] relative">
                                                        {{-- Instant Loading Overlay --}}
                                                        <div wire:loading.flex wire:target="search" class="absolute inset-0 bg-base-100/60 backdrop-blur-[1px] z-20 items-center justify-center">
                                                            <span class="loading loading-spinner loading-md text-base-content/20"></span>
                                                        </div>
                                                                            <x-mary-table 
                        class="table-zebra table-md w-full"
                        :headers="[
                            ['key' => 'title', 'label' => __('internship::ui.title')],
                            ['key' => 'year', 'label' => __('internship::ui.year')],
                            ['key' => 'semester', 'label' => __('internship::ui.semester')],
                            ['key' => 'date_start', 'label' => __('internship::ui.date_start')],
                            ['key' => 'date_finish', 'label' => __('internship::ui.date_finish')],
                            ['key' => 'actions', 'label' => '', 'class' => 'w-1'],
                        ]" 
                        :rows="$records" 
                        with-pagination
                    >
    
                    @scope('cell_date_start', $program)
                        <span class="text-xs opacity-70">{{ $program->date_start->translatedFormat('d M Y') }}</span>
                    @endscope
                    @scope('cell_date_finish', $program)
                        <span class="text-xs opacity-70">{{ $program->date_finish->translatedFormat('d M Y') }}</span>
                    @endscope

                    @scope('cell_actions', $program)
                        <div class="flex items-center justify-end gap-1">
                            <x-ui::button icon="tabler.edit" variant="tertiary" class="text-info btn-xs" wire:click="edit('{{ $program->id }}')" tooltip="{{ __('ui::common.edit') }}" />
                            <x-ui::button icon="tabler.trash" variant="tertiary" class="text-error btn-xs" wire:click="discard('{{ $program->id }}')" tooltip="{{ __('ui::common.delete') }}" />
                        </div>
                    @endscope
                </x-mary-table>
            </div>
        </x-ui::card>

    {{-- Form Modal --}}
    <x-ui::modal id="internship-form-modal" wire:model="formModal" :title="$form->id ? __('internship::ui.edit_program') : __('internship::ui.add_program')">
        <x-ui::form wire:submit.prevent="save">
            <x-ui::input :label="__('internship::ui.title')" icon="tabler.presentation" wire:model="form.title" required />
            <x-ui::textarea :label="__('ui::common.description')" icon="tabler.align-left" wire:model="form.description" />
            
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui::input :label="__('internship::ui.year')" icon="tabler.calendar-event" type="number" wire:model="form.year" required />
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

            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" wire:click="$set('formModal', false)" />
                <x-ui::button :label="__('ui::common.save')" type="submit" variant="primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    {{-- Confirm Delete Modal --}}
    <x-ui::modal id="internship-confirm-modal" wire:model="confirmModal" :title="__('ui::common.confirm')">
        <p>{{ __('internship::ui.delete_program_confirm') }}</p>
        <x-slot:actions>
            <x-ui::button :label="__('ui::common.cancel')" wire:click="$set('confirmModal', false)" />
            <x-ui::button :label="__('ui::common.delete')" class="btn-error" wire:click="remove('{{ $recordId }}')" spinner="remove" />
        </x-slot:actions>
    </x-ui::modal>

    {{-- Import Modal --}}
    <x-ui::modal id="internship-import-modal" wire:model="importModal" :title="__('ui::common.import')">
        <x-ui::form wire:submit.prevent="importCsv">
            <div class="mb-4 flex items-center justify-between px-1">
                <span class="text-xs font-bold uppercase tracking-widest text-base-content/40">{{ __('ui::common.select_file') }}</span>
                <x-ui::button :label="__('ui::common.download_template')" icon="tabler.file-download" variant="tertiary" class="btn-xs" wire:click="downloadTemplate" />
            </div>

            <x-ui::file 
                wire:model="csvFile" 
                accept=".csv"
                :crop="false"
                required
            />
            <p class="text-xs text-base-content/50 mt-2 italic">
                Format CSV: Title, Description, Year, Semester, Start Date (YYYY-MM-DD), Finish Date (YYYY-MM-DD)
            </p>

            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" wire:click="$set('importModal', false)" />
                <x-ui::button :label="__('ui::common.import')" type="submit" variant="primary" spinner="importCsv" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>
</div>
