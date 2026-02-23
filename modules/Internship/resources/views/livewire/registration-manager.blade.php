<div>
    <x-ui::header 
        wire:key="registration-manager-header"
        :title="__('internship::ui.registration_title')" 
        :subtitle="__('internship::ui.registration_subtitle')"
    >
        <x-slot:actions wire:key="registration-manager-actions">
            <div class="flex items-center gap-3 relative z-50">
                <x-ui::dropdown icon="tabler.dots" variant="tertiary" right>
                    <x-ui::menu-item title="ui::common.print" icon="tabler.printer" wire:click="printPdf" />
                    <x-ui::menu-item title="ui::common.export" icon="tabler.download" wire:click="exportCsv" />
                    <x-ui::menu-item title="ui::common.import" icon="tabler.upload" wire:click="$set('importModal', true)" />
                </x-ui::dropdown>

                <x-ui::dropdown 
                    :label="__('internship::ui.bulk_actions')" 
                    icon="tabler.layers-intersect" 
                    variant="secondary" 
                    :disabled="count($selectedIds) === 0"
                >
                    <x-ui::menu-item 
                        title="internship::ui.bulk_placement" 
                        icon="tabler.map-pin-up" 
                        wire:click="openBulkPlace" 
                    />
                    <x-ui::menu-item 
                        title="internship::ui.delete_selected" 
                        icon="tabler.trash" 
                        class="text-error" 
                        wire:click="removeSelected" 
                        wire:confirm="{{ __('ui::common.delete_confirm') }}"
                    />
                </x-ui::dropdown>

                <x-ui::button :label="__('internship::ui.add_registration')" icon="tabler.plus" variant="primary" wire:click="add" />
            </div>
        </x-slot:actions>
    </x-ui::header>
    
        <x-ui::card>
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
                        :placeholder="__('internship::ui.search_registration')" 
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

                <x-ui::table :headers="[
                ['key' => 'student.name', 'label' => __('internship::ui.student')],
                ['key' => 'internship.title', 'label' => __('internship::ui.program')],
                ['key' => 'requirements', 'label' => __('internship::ui.requirements')],
                ['key' => 'placement.company_name', 'label' => __('internship::ui.placement')],
                ['key' => 'teacher.name', 'label' => __('internship::ui.teacher')],
                ['key' => 'mentor.name', 'label' => __('internship::ui.mentor')],
                ['key' => 'status', 'label' => __('internship::ui.status')],
                ['key' => 'actions', 'label' => ''],
            ]" :rows="$records" wire:model="selectedIds" selectable with-pagination>
                @scope('cell_requirements', $registration)
                    @php
                        $percentage = $registration->getRequirementCompletionPercentage();
                        $cleared = $registration->hasClearedAllMandatoryRequirements();
                    @endphp
                    <div class="flex items-center gap-2">
                        <div class="radial-progress text-primary text-[10px]" style="--value:{{ $percentage }}; --size: 1.5rem; --thickness: 2px;">
                            {{ floor($percentage) }}%
                        </div>
                        @if($cleared)
                             <x-ui::icon name="tabler.circle-check" class="w-4 h-4 text-success" />
                        @endif
                    </div>
                @endscope

                @scope('cell_status', $registration)
                    <x-ui::badge :label="$registration->getStatusLabel()" :class="'badge-' . $registration->getStatusColor()" />
                @endscope

                @scope('cell_actions', $registration)
                    <div class="flex gap-2">
                        <x-ui::button icon="tabler.history" class="btn-ghost btn-sm text-secondary" wire:click="viewHistory('{{ $registration->id }}')" tooltip="{{ __('internship::ui.placement_history') }}" />
                        @if($registration->latestStatus()?->name !== 'active')
                            <x-ui::button icon="tabler.check" class="btn-ghost btn-sm text-success" wire:click="approve('{{ $registration->id }}')" tooltip="{{ __('shared::ui.approve') }}" />
                        @endif
                        @if($registration->latestStatus()?->name === 'active')
                            <x-ui::button icon="tabler.award" class="btn-ghost btn-sm text-primary" wire:click="complete('{{ $registration->id }}')" tooltip="{{ __('internship::ui.complete_program') }}" />
                        @endif
                        @if($registration->latestStatus()?->name !== 'inactive')
                            <x-ui::button icon="tabler.x" class="btn-ghost btn-sm text-warning" wire:click="reject('{{ $registration->id }}')" tooltip="{{ __('shared::ui.reject') }}" />
                        @endif
                        <x-ui::button icon="tabler.edit" class="btn-ghost btn-sm text-info" wire:click="edit('{{ $registration->id }}')" tooltip="{{ __('shared::ui.edit') }}" />
                        <x-ui::button icon="tabler.trash" class="btn-ghost btn-sm text-error" wire:click="discard('{{ $registration->id }}')" tooltip="{{ __('shared::ui.delete') }}" />
                    </div>
                @endscope
            </x-ui::table>
        </div>
    </x-ui::card>

    {{-- Form Modal --}}
    <x-ui::modal id="registration-form-modal" wire:model="formModal" :title="$form->id ? __('internship::ui.edit_registration') : __('internship::ui.add_registration')">
        <x-ui::form wire:submit.prevent="save">
            <x-ui::select 
                :label="__('internship::ui.student')" 
                icon="tabler.user"
                wire:model="form.student_id" 
                :options="$this->students" 
                :placeholder="__('internship::ui.select_student')"
                required 
            />
            <x-ui::select 
                :label="__('internship::ui.program')" 
                icon="tabler.presentation"
                wire:model="form.internship_id" 
                :options="$this->internships" 
                option-label="title"
                :placeholder="__('internship::ui.select_program')"
                required 
            />
            <x-ui::select 
                :label="__('internship::ui.placement')" 
                icon="tabler.building-community"
                wire:model="form.placement_id" 
                :options="$this->placements" 
                :placeholder="__('internship::ui.select_placement')"
                required 
            />

            <div class="divider opacity-10"></div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui::select 
                    :label="__('internship::ui.teacher')" 
                    icon="tabler.school"
                    wire:model="form.teacher_id" 
                    :options="$this->teachers" 
                    :placeholder="__('internship::ui.select_teacher')"
                    required
                />
                <x-ui::select 
                    :label="__('internship::ui.mentor')" 
                    icon="tabler.briefcase"
                    wire:model="form.mentor_id" 
                    :options="$this->mentors" 
                    :placeholder="__('internship::ui.select_mentor')"
                />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui::input 
                    :label="__('internship::ui.date_start')" 
                    icon="tabler.calendar-event"
                    type="date" 
                    wire:model="form.start_date" 
                    required 
                />
                <x-ui::input 
                    :label="__('internship::ui.date_finish')" 
                    icon="tabler.calendar-check"
                    type="date" 
                    wire:model="form.end_date" 
                    required 
                />
            </div>

            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" wire:click="$set('formModal', false)" />
                <x-ui::button :label="__('ui::common.save')" type="submit" variant="primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    {{-- Confirm Delete Modal --}}
    <x-ui::modal id="registration-confirm-modal" wire:model="confirmModal" title="{{ __('shared::ui.confirmation') }}">
        <p>{{ __('internship::ui.delete_registration_confirm') }}</p>
        <x-slot:actions>
            <x-ui::button label="{{ __('shared::ui.cancel') }}" wire:click="$set('confirmModal', false)" />
            <x-ui::button label="{{ __('shared::ui.delete') }}" class="btn-error" wire:click="remove('{{ $recordId }}')" spinner="remove" />
        </x-slot:actions>
    </x-ui::modal>

    {{-- Bulk Placement Modal --}}
    <x-ui::modal id="registration-bulk-modal" wire:model="bulkPlaceModal" title="{{ __('internship::ui.bulk_placement_title') }}">
        <div class="mb-4">
            <p class="text-sm opacity-70">{{ __('internship::ui.bulk_placement_description', ['count' => count($selectedIds)]) }}</p>
            <p class="text-xs text-warning mt-1">{{ __('internship::ui.bulk_placement_note') }}</p>
        </div>

        <x-ui::form wire:submit.prevent="executeBulkPlace">
            <x-ui::select 
                label="{{ __('internship::ui.placement_location') }}" 
                wire:model="targetPlacementId" 
                :options="$this->placements" 
                placeholder="{{ __('internship::ui.select_industry_partner') }}"
                required 
            />

            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" wire:click="$set('bulkPlaceModal', false)" />
                <x-ui::button :label="__('internship::ui.process_placement')" type="submit" variant="primary" spinner="executeBulkPlace" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    {{-- History Modal --}}
    <x-ui::modal id="registration-history-modal" wire:model="historyModal" title="{{ __('internship::ui.student_placement_history') }}" separator>
        @if($historyId)
            <div class="flex flex-col gap-4">
                @forelse($this->history as $log)
                    <div class="flex gap-4 border-l-2 border-primary/30 pl-4 py-2 relative">
                        <div class="absolute -left-[5px] top-4 w-2 h-2 rounded-full bg-primary"></div>
                        <div class="flex-1">
                            <div class="flex justify-between items-start">
                                <span class="font-bold text-sm uppercase text-primary">{{ $log->action }}</span>
                                <span class="text-xs opacity-50">{{ $log->created_at->format('d M Y, H:i') }}</span>
                            </div>
                            <div class="text-sm font-semibold mt-1">{{ $log->placement?->company_name ?? __('ui::common.not_applicable') }}</div>
                            @if($log->reason)
                                <div class="text-xs opacity-70 italic mt-1">"{{ $log->reason }}"</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-center py-8 opacity-50">{{ __('internship::ui.no_history_yet') }}</p>
                @endforelse
            </div>
        @endif
        <x-slot:actions>
            <x-ui::button :label="__('ui::common.close')" wire:click="$set('historyModal', false)" />
        </x-slot:actions>
    </x-ui::modal>

    {{-- Import Modal --}}
    <x-ui::modal id="registration-import-modal" wire:model="importModal" :title="__('ui::common.import')">
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

            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" wire:click="$set('importModal', false)" />
                <x-ui::button :label="__('ui::common.import')" type="submit" variant="primary" spinner="importCsv" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>
</div>
