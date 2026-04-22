<div x-data="{ tab: @entangle('activeTab') }">
    <x-ui::header
        :title="$this->title"
        :subtitle="$this->subtitle"
    >
        <x-slot:actions wire:key="{{ $this->getEventPrefix() }}-actions">
            <div class="flex items-center gap-3">
                <x-ui::button
                    :label="__('ui::common.refresh')"
                    icon="tabler.refresh"
                    variant="secondary"
                    wire:click="refreshRecords"
                    spinner="refreshRecords"
                />
            </div>
        </x-slot:actions>
    </x-ui::header>

    <x-ui::card>
        {{-- Tab Navigation --}}
        <div class="tabs tabs-bordered mb-6" role="tablist">
            <button
                type="button"
                role="tab"
                @click="tab = 'individual'"
                :aria-selected="tab === 'individual'"
                :class="{'tab-active': tab === 'individual'}"
                class="tab font-semibold"
            >
                <x-ui::icon name="tabler.user-check" class="size-5" />
                {{ __('internship::ui.individual_placement') }}
            </button>
            <button
                type="button"
                role="tab"
                @click="tab = 'bulk'"
                :aria-selected="tab === 'bulk'"
                :class="{'tab-active': tab === 'bulk'}"
                class="tab font-semibold"
            >
                <x-ui::icon name="tabler.users-group" class="size-5" />
                {{ __('internship::ui.bulk_placement') }}
            </button>
        </div>

        {{-- INDIVIDUAL PLACEMENT TAB --}}
        <div x-show="tab === 'individual'" role="tabpanel" class="space-y-4">
            <x-ui::header
                :title="__('internship::ui.registration_title')"
                :subtitle="__('internship::ui.registration_subtitle')"
                class="!bg-transparent !shadow-none !px-0 !py-2"
            >
                <x-slot:actions>
                    @if($this->can('create'))
                        <x-ui::button
                            :label="$this->addLabel"
                            icon="tabler.plus"
                            variant="primary"
                            wire:click="add"
                        />
                    @endif
                </x-slot:actions>
            </x-ui::header>

            {{-- Search and Table --}}
            <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full md:w-1/3">
                    <x-ui::input
                        :placeholder="__('ui::common.search_placeholder')"
                        icon="tabler.search"
                        wire:model.live.debounce.500ms="search"
                        clearable
                    />
                </div>
            </div>

            {{-- Registrations Table --}}
            <div class="w-full overflow-auto rounded-xl border border-base-200 bg-base-100 shadow-sm max-h-[60vh]">
                <table class="table table-zebra table-md w-full">
                    <thead>
                        <tr>
                            <th>{{ __('internship::ui.student') }}</th>
                            <th>{{ __('internship::ui.program') }}</th>
                            <th>{{ __('internship::ui.placement') }}</th>
                            <th>{{ __('internship::ui.teacher') }}</th>
                            <th>{{ __('internship::ui.status') }}</th>
                            <th class="text-right">{{ __('ui::common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->records as $registration)
                            <tr>
                                <td class="font-semibold">{{ $registration->student_name }}</td>
                                <td>{{ $registration->internship_title }}</td>
                                <td>{{ $registration->placement_company }}</td>
                                <td>{{ $registration->teacher_name }}</td>
                                <td>
                                    <x-ui::badge
                                        :value="$registration->status"
                                        :variant="match($registration->status) {
                                            'approved' => 'success',
                                            'pending' => 'warning',
                                            'rejected' => 'error',
                                            default => 'secondary'
                                        }"
                                    />
                                </td>
                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        <x-ui::button
                                            icon="tabler.edit"
                                            variant="tertiary"
                                            class="text-info btn-xs"
                                            wire:click="edit('{{ $registration->id }}')"
                                            tooltip="{{ __('ui::common.edit') }}"
                                        />
                                        <x-ui::button
                                            icon="tabler.trash"
                                            variant="tertiary"
                                            class="text-error btn-xs"
                                            wire:click="discard('{{ $registration->id }}')"
                                            tooltip="{{ __('ui::common.delete') }}"
                                        />
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-8">
                                    <p class="text-base-content/60">{{ __('ui::common.no_results') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($this->records->hasPages())
                <div class="mt-4 flex items-center justify-between">
                    <div class="text-sm text-base-content/60">
                        {{ __('ui::common.showing', ['from' => $this->records->firstItem(), 'to' => $this->records->lastItem(), 'total' => $this->records->total()]) }}
                    </div>
                    {{ $this->records->links('pagination::tailwind') }}
                </div>
            @endif
        </div>

        {{-- BULK PLACEMENT TAB --}}
        <div x-show="tab === 'bulk'" role="tabpanel" class="space-y-6">
            <x-ui::header
                :title="__('internship::ui.bulk_placement_title')"
                :subtitle="__('internship::ui.bulk_placement_description', ['count' => count($this->selectedStudents)])"
                class="!bg-transparent !shadow-none !px-0 !py-2"
            />

            {{-- Step 1: Select Internship & Company --}}
            <div class="bg-base-100 rounded-xl border border-base-200 p-6">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <x-ui::badge :value="1" class="badge-lg" />
                    {{ __('internship::ui.select_internship_company') }}
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui::select
                        :label="__('internship::ui.program')"
                        icon="tabler.presentation"
                        wire:model.live="internshipId"
                        :options="$this->internships()"
                        :placeholder="__('ui::common.select')"
                        required
                    />

                    <x-ui::select
                        :label="__('internship::ui.placement')"
                        icon="tabler.building"
                        wire:model.live="companyId"
                        :options="$this->companies()"
                        :placeholder="__('ui::common.select')"
                        :disabled="!$internshipId"
                        required
                    />
                </div>

                {{-- Quota Display --}}
                @if($internshipId && $companyId)
                    <div class="mt-4 alert alert-info">
                        <x-ui::icon name="tabler.info-circle" class="size-5" />
                        <div>
                            <p class="font-semibold">{{ __('internship::ui.quota_info') }}</p>
                            <p class="text-sm mt-1">{{ __('internship::ui.remaining_quota', ['count' => $this->remainingQuota()]) }}</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Step 2: Select Students --}}
            @if($internshipId)
                <div class="bg-base-100 rounded-xl border border-base-200 p-6">
                    <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                        <x-ui::badge :value="2" class="badge-lg" />
                        {{ __('internship::ui.select_students') }}
                        @if($this->availableStudents())
                            <x-ui::badge :value="count($this->availableStudents())" variant="info" class="badge-sm" />
                        @endif
                    </h3>

                    @if($this->availableStudents())
                        {{-- Select All / Deselect All --}}
                        <div class="mb-4 flex items-center gap-2">
                            <input
                                type="checkbox"
                                class="checkbox"
                                @change="$wire.selectedStudents = $event.target.checked ? $wire.availableStudents().map(s => s.id) : []"
                                :checked="selectedStudents.length === availableStudents().length && availableStudents().length > 0"
                            />
                            <span class="font-semibold">{{ __('internship::ui.select_all') }}</span>
                            <span class="text-sm text-base-content/60">({{ count($this->availableStudents()) }} available)</span>
                        </div>

                        {{-- Student List --}}
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            @foreach($this->availableStudents() as $student)
                                <label class="flex items-center gap-3 p-3 rounded-lg border border-base-200 hover:bg-base-200 cursor-pointer transition">
                                    <input
                                        type="checkbox"
                                        class="checkbox"
                                        wire:model="selectedStudents"
                                        value="{{ $student['id'] }}"
                                    />
                                    <div class="flex-1">
                                        <p class="font-semibold">{{ $student['name'] }}</p>
                                        <p class="text-sm text-base-content/60">{{ $student['email'] }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        {{-- Selection Summary --}}
                        @if(count($selectedStudents) > 0)
                            <div class="mt-4 alert alert-success">
                                <x-ui::icon name="tabler.circle-check" class="size-5" />
                                <p class="font-semibold">{{ __('internship::ui.selected_students', ['count' => count($selectedStudents)]) }}</p>
                            </div>
                        @endif
                    @else
                        <div class="alert alert-warning">
                            <x-ui::icon name="tabler.alert-circle" class="size-5" />
                            <p>{{ __('internship::ui.no_unplaced_students') }}</p>
                        </div>
                    @endif
                </div>

                {{-- Step 3: Review & Place --}}
                @if($companyId && count($selectedStudents) > 0)
                    <div class="bg-base-100 rounded-xl border border-base-200 p-6">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <x-ui::badge :value="3" class="badge-lg" />
                            {{ __('internship::ui.preview_placement') }}
                        </h3>

                        <div class="alert alert-info mb-4">
                            <x-ui::icon name="tabler.info-circle" class="size-5" />
                            <p>{{ __('internship::ui.placement_cannot_undo') }}</p>
                        </div>

                        <div class="flex gap-3">
                            <x-ui::button
                                :label="__('internship::ui.confirm_placement')"
                                icon="tabler.check"
                                class="btn-success"
                                wire:click="showBulkConfirmation"
                            />
                            <x-ui::button
                                :label="__('ui::common.cancel')"
                                icon="tabler.x"
                                variant="secondary"
                                wire:click="resetBulkForm"
                            />
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </x-ui::card>

    {{-- Form Modal (for individual registration editing) --}}
    <x-ui::modal wire:model="formModal" :title="$this->form->id ? __('internship::ui.edit_registration') : __('internship::ui.add_registration')">
        <x-ui::form wire:submit="save">
            <div class="space-y-4">
                <p class="text-sm text-base-content/60">{{ __('internship::ui.registration_form_help') }}</p>

                <x-ui::select
                    :label="__('internship::ui.program')"
                    icon="tabler.book"
                    wire:model.live="form.internship_id"
                    :options="$this->internships()"
                    :placeholder="__('ui::common.select')"
                    required
                    error="{{ $errors->first('form.internship_id') }}"
                />

                <x-ui::select
                    :label="__('internship::ui.student')"
                    icon="tabler.user"
                    wire:model="form.student_id"
                    :options="$this->getStudents()"
                    :placeholder="__('ui::common.select')"
                    required
                    error="{{ $errors->first('form.student_id') }}"
                />

                <x-ui::select
                    :label="__('internship::ui.placement')"
                    icon="tabler.building"
                    wire:model="form.placement_id"
                    :options="$this->getPlacements()"
                    :placeholder="__('ui::common.select')"
                    required
                    error="{{ $errors->first('form.placement_id') }}"
                />

                <x-ui::select
                    :label="__('internship::ui.teacher')"
                    icon="tabler.chalkboard"
                    wire:model="form.teacher_id"
                    :options="$this->getTeachers()"
                    :placeholder="__('ui::common.select')"
                    required
                    error="{{ $errors->first('form.teacher_id') }}"
                />

                <x-ui::select
                    :label="__('internship::ui.mentor')"
                    icon="tabler.users-group"
                    wire:model="form.mentor_id"
                    :options="$this->getMentors()"
                    :placeholder="__('ui::common.select')"
                    error="{{ $errors->first('form.mentor_id') }}"
                />

                <x-ui::input
                    type="date"
                    :label="__('internship::ui.start_date')"
                    icon="tabler.calendar-event"
                    wire:model="form.start_date"
                    required
                    error="{{ $errors->first('form.start_date') }}"
                />

                <x-ui::input
                    type="date"
                    :label="__('internship::ui.end_date')"
                    icon="tabler.calendar-event"
                    wire:model="form.end_date"
                    required
                    error="{{ $errors->first('form.end_date') }}"
                />
            </div>

            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" x-on:click="$wire.formModal = false" />
                <x-ui::button :label="__('ui::common.save')" type="submit" variant="primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    {{-- Confirm Delete Modal --}}
    <x-ui::modal wire:model="confirmModal" :title="__('ui::common.confirm')">
        <p>{{ $this->deleteConfirmMessage }}</p>
        <x-slot:actions>
            <x-ui::button :label="__('ui::common.cancel')" x-on:click="$wire.confirmModal = false" />
            <x-ui::button :label="__('ui::common.delete')" class="btn-error" wire:click="remove('{{ $this->recordId }}')" spinner="remove" />
        </x-slot:actions>
    </x-ui::modal>

    {{-- Bulk Placement Confirmation Modal --}}
    <x-ui::modal wire:model="bulkConfirmModal" :title="__('internship::ui.confirm_placement_title')">
        <div class="space-y-4">
            <p>{{ __('internship::ui.confirm_placement_message') }}</p>

            <div class="bg-base-200 rounded-lg p-4 space-y-2">
                <p class="text-sm font-semibold">{{ __('internship::ui.placement_summary') }}:</p>
                <p><span class="font-semibold">Students:</span> {{ count($selectedStudents) }}</p>
                <p><span class="font-semibold">Remaining Quota:</span> {{ $this->remainingQuota() }}</p>
            </div>
        </div>

        <x-slot:actions>
            <x-ui::button
                :label="__('ui::common.cancel')"
                x-on:click="$wire.bulkConfirmModal = false"
            />
            <x-ui::button
                :label="__('internship::ui.confirm_placement')"
                class="btn-success"
                wire:click="executeBulkPlacement"
                spinner="executeBulkPlacement"
            />
        </x-slot:actions>
    </x-ui::modal>
</div>
