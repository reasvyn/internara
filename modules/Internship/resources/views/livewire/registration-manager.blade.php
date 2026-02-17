<div>
    <x-ui::main title="{{ __('internship::ui.registration_title') }}" subtitle="{{ __('internship::ui.registration_subtitle') }}">
        <x-slot:actions>
            <x-ui::button label="{{ __('Bulk Penempatan') }}" icon="tabler.layers-intersect" class="btn-outline" wire:click="openBulkPlace" />
            <x-ui::button label="{{ __('internship::ui.add_registration') }}" icon="tabler.plus" class="btn-primary" wire:click="add" />
        </x-slot:actions>

        <x-ui::card>
            <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full md:w-1/3">
                    <x-ui::input placeholder="{{ __('internship::ui.search_registration') }}" icon="tabler.search" wire:model.live.debounce.300ms="search" clearable />
                </div>
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
            ]" :rows="$this->records" wire:model="selectedIds" selectable with-pagination>
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
                        <x-ui::button icon="tabler.history" class="btn-ghost btn-sm text-secondary" wire:click="viewHistory('{{ $registration->id }}')" tooltip="{{ __('Riwayat Penempatan') }}" />
                        @if($registration->latestStatus()?->name !== 'active')
                            <x-ui::button icon="tabler.check" class="btn-ghost btn-sm text-success" wire:click="approve('{{ $registration->id }}')" tooltip="{{ __('shared::ui.approve') }}" />
                        @endif
                        @if($registration->latestStatus()?->name === 'active')
                            <x-ui::button icon="tabler.award" class="btn-ghost btn-sm text-primary" wire:click="complete('{{ $registration->id }}')" tooltip="{{ __('Selesaikan Program') }}" />
                        @endif
                        @if($registration->latestStatus()?->name !== 'inactive')
                            <x-ui::button icon="tabler.x" class="btn-ghost btn-sm text-warning" wire:click="reject('{{ $registration->id }}')" tooltip="{{ __('shared::ui.reject') }}" />
                        @endif
                        <x-ui::button icon="tabler.edit" class="btn-ghost btn-sm text-info" wire:click="edit('{{ $registration->id }}')" tooltip="{{ __('shared::ui.edit') }}" />
                        <x-ui::button icon="tabler.trash" class="btn-ghost btn-sm text-error" wire:click="discard('{{ $registration->id }}')" tooltip="{{ __('shared::ui.delete') }}" />
                    </div>
                @endscope
            </x-ui::table>
        </x-ui::card>
    </x-ui::main>

    {{-- Form Modal --}}
    <x-ui::modal id="registration-form-modal" wire:model="formModal" title="{{ $form->id ? __('internship::ui.edit_registration') : __('internship::ui.add_registration') }}">
        <x-ui::form wire:submit="save">
            <x-ui::select 
                label="{{ __('internship::ui.student') }}" 
                wire:model="form.student_id" 
                :options="$this->students" 
                placeholder="{{ __('internship::ui.select_student') }}"
                required 
            />
            <x-ui::select 
                label="{{ __('internship::ui.program') }}" 
                wire:model="form.internship_id" 
                :options="$this->internships" 
                placeholder="{{ __('internship::ui.select_program') }}"
                required 
            />
            <x-ui::select 
                label="{{ __('internship::ui.placement') }}" 
                wire:model="form.placement_id" 
                :options="$this->placements" 
                placeholder="{{ __('internship::ui.select_placement') }}"
                required 
            />

            <hr class="my-4 opacity-20" />

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui::select 
                    label="{{ __('internship::ui.teacher') }}" 
                    wire:model="form.teacher_id" 
                    :options="$this->teachers" 
                    placeholder="{{ __('internship::ui.select_teacher') }}"
                    required
                />
                <x-ui::select 
                    label="{{ __('internship::ui.mentor') }}" 
                    wire:model="form.mentor_id" 
                    :options="$this->mentors" 
                    placeholder="{{ __('internship::ui.select_mentor') }}"
                />
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui::input label="{{ __('internship::ui.date_start') }}" type="date" wire:model="form.start_date" required />
                <x-ui::input label="{{ __('internship::ui.date_finish') }}" type="date" wire:model="form.end_date" required />
            </div>

            <x-slot:actions>
                <x-ui::button label="{{ __('shared::ui.cancel') }}" wire:click="$set('formModal', false)" />
                <x-ui::button label="{{ __('shared::ui.save') }}" type="submit" class="btn-primary" spinner="save" />
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
    <x-ui::modal id="registration-bulk-modal" wire:model="bulkPlaceModal" title="{{ __('Penempatan Massal') }}">
        <div class="mb-4">
            <p class="text-sm opacity-70">{{ __(':count siswa terpilih akan ditempatkan di lokasi yang sama.', ['count' => count($selectedIds)]) }}</p>
            <p class="text-xs text-warning mt-1">{{ __('Catatan: Hanya siswa yang sudah melengkapi persyaratan wajib yang akan diproses.') }}</p>
        </div>

        <x-ui::form wire:submit="executeBulkPlace">
            <x-ui::select 
                label="{{ __('Lokasi Penempatan') }}" 
                wire:model="targetPlacementId" 
                :options="$this->placements" 
                placeholder="{{ __('Pilih Industri Partner') }}"
                required 
            />

            <x-slot:actions>
                <x-ui::button label="{{ __('Batal') }}" wire:click="$set('bulkPlaceModal', false)" />
                <x-ui::button label="{{ __('Proses Penempatan') }}" type="submit" class="btn-primary" spinner="executeBulkPlace" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    {{-- History Modal --}}
    <x-ui::modal id="registration-history-modal" wire:model="historyModal" title="{{ __('Riwayat Penempatan Siswa') }}" separator>
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
                            <div class="text-sm font-semibold mt-1">{{ $log->placement?->company_name ?? __('N/A') }}</div>
                            @if($log->reason)
                                <div class="text-xs opacity-70 italic mt-1">"{{ $log->reason }}"</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-center py-8 opacity-50">{{ __('Belum ada riwayat penempatan.') }}</p>
                @endforelse
            </div>
        @endif
        <x-slot:actions>
            <x-ui::button label="{{ __('Tutup') }}" wire:click="$set('historyModal', false)" />
        </x-slot:actions>
    </x-ui::modal>
</div>
