<div x-data="{ tab: @entangle('activeTab') }" class="space-y-8">
    <x-ui::header :title="$this->title" :subtitle="$this->subtitle">
        <x-slot:actions wire:key="{{ $this->getEventPrefix() }}-actions">
            <div class="flex items-center gap-3">
                <x-ui::button :label="__('ui::common.refresh')" icon="tabler.refresh" variant="secondary" wire:click="refreshRecords" spinner="refreshRecords" />
            </div>
        </x-slot:actions>
    </x-ui::header>

    {{-- Executive Summary: Premium Stats Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        <x-ui::stat 
            :title="__('internship::ui.stats.total_registrations')" 
            :value="$this->stats['total']" 
            icon="tabler.users" 
            variant="metadata" 
            class="shadow-sm border border-base-content/5 bg-base-100/50" 
        />
        <x-ui::stat 
            :title="__('internship::ui.stats.placed_students')" 
            :value="$this->stats['placed']" 
            icon="tabler.user-check" 
            variant="success" 
            class="shadow-sm border border-base-content/5 bg-base-100/50" 
        />
        <x-ui::stat 
            :title="__('internship::ui.stats.unplaced_students')" 
            :value="$this->stats['unplaced']" 
            icon="tabler.user-question" 
            variant="warning" 
            class="shadow-sm border border-base-content/5 bg-base-100/50" 
        />
        <x-ui::stat 
            :title="__('internship::ui.stats.new_registrations')" 
            :value="$this->stats['new']" 
            icon="tabler.sparkles" 
            variant="primary" 
            class="shadow-sm border border-base-content/5 bg-base-100/50" 
        />
    </div>

    <x-ui::card class="!p-0 overflow-hidden border-none shadow-xl">
        {{-- Premium Tab Navigation --}}
        <div class="flex items-center bg-base-200/50 p-1 gap-1">
            <button
                type="button"
                @click="tab = 'individual'"
                :class="tab === 'individual' ? 'bg-base-100 shadow-sm text-primary' : 'text-base-content/50 hover:bg-base-100/50'"
                class="flex-1 flex items-center justify-center gap-2 py-3 px-4 rounded-lg font-bold text-xs uppercase tracking-widest transition-all duration-200"
            >
                <x-ui::icon name="tabler.user-check" class="size-4" />
                {{ __('internship::ui.individual_placement') }}
            </button>
            <button
                type="button"
                @click="tab = 'bulk'"
                :class="tab === 'bulk' ? 'bg-base-100 shadow-sm text-primary' : 'text-base-content/50 hover:bg-base-100/50'"
                class="flex-1 flex items-center justify-center gap-2 py-3 px-4 rounded-lg font-bold text-xs uppercase tracking-widest transition-all duration-200"
            >
                <x-ui::icon name="tabler.users-group" class="size-4" />
                {{ __('internship::ui.bulk_placement') }}
                @if($this->stats['unplaced'] > 0)
                    <span class="badge badge-warning badge-sm animate-pulse">{{ $this->stats['unplaced'] }}</span>
                @endif
            </button>
        </div>

        <div class="p-6">
            {{-- INDIVIDUAL PLACEMENT TAB --}}
            <div x-show="tab === 'individual'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" role="tabpanel" class="space-y-6">
                {{-- Search and Actions --}}
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="w-full md:w-1/3">
                        <x-ui::input :placeholder="__('ui::common.search_placeholder')" icon="tabler.search" wire:model.live.debounce.500ms="search" clearable />
                    </div>
                    @if($this->can('create'))
                        <x-ui::button :label="$this->addLabel" icon="tabler.plus" variant="primary" wire:click="add" />
                    @endif
                </div>

                {{-- Registrations Table --}}
                <div class="w-full overflow-auto rounded-2xl border border-base-content/5 bg-base-100 shadow-sm">
                    <table class="table table-md w-full">
                        <thead class="bg-base-200/50">
                            <tr>
                                <th class="text-[10px] uppercase tracking-widest opacity-50">{{ __('internship::ui.student') }}</th>
                                <th class="text-[10px] uppercase tracking-widest opacity-50">{{ __('internship::ui.program') }}</th>
                                <th class="text-[10px] uppercase tracking-widest opacity-50">{{ __('internship::ui.placement') }}</th>
                                <th class="text-[10px] uppercase tracking-widest opacity-50">{{ __('internship::ui.readiness') }}</th>
                                <th class="text-[10px] uppercase tracking-widest opacity-50">{{ __('internship::ui.status') }}</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($this->records as $registration)
                                <tr class="hover:bg-base-200/30 transition-colors">
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="avatar placeholder">
                                                <div class="bg-primary/10 text-primary rounded-xl size-10">
                                                    @if($registration->student_avatar)
                                                        <img src="{{ $registration->student_avatar }}" />
                                                    @else
                                                        <span class="text-xs font-bold">{{ substr($registration->student_name, 0, 2) }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="font-bold text-sm">{{ $registration->student_name }}</span>
                                                <span class="text-[10px] opacity-40 uppercase tracking-tighter">{{ __('internship::ui.teacher') }}: {{ $registration->teacher_name }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-sm opacity-80">{{ $registration->internship_title }}</td>
                                    <td>
                                        @if($registration->placement_company !== '-')
                                            <div class="flex items-center gap-2">
                                                <x-ui::icon name="tabler.building" class="size-4 text-primary" />
                                                <span class="text-sm font-semibold">{{ $registration->placement_company }}</span>
                                            </div>
                                        @elseif($registration->proposed_company_name)
                                            <div class="flex flex-col gap-1">
                                                <x-ui::badge value="{{ __('internship::ui.propose_new_partner') }}" variant="warning" class="badge-xs font-black text-[8px] uppercase" />
                                                <span class="text-sm italic opacity-60">{{ $registration->proposed_company_name }}</span>
                                            </div>
                                        @else
                                            <span class="text-xs opacity-30 italic">{{ __('internship::ui.not_placed') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex flex-col gap-1 w-24">
                                            <div class="flex items-center justify-between text-[8px] font-black uppercase tracking-tighter">
                                                <span>{{ $registration->readiness }}%</span>
                                                <span class="opacity-40">{{ __('internship::ui.complete') }}</span>
                                            </div>
                                            <div class="h-1 w-full bg-base-content/5 rounded-full overflow-hidden">
                                                <div class="h-full bg-success transition-all duration-500" style="width: {{ $registration->readiness }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <x-ui::badge 
                                            :value="$registration->status" 
                                            :variant="match($registration->status) {
                                                'approved' => 'success',
                                                'pending' => 'warning',
                                                'rejected' => 'error',
                                                default => 'neutral'
                                            }"
                                            class="badge-sm font-black text-[9px] uppercase tracking-tighter"
                                        />
                                    </td>
                                    <td>
                                        <div class="flex items-center justify-end gap-1">
                                            <x-ui::button icon="tabler.edit" variant="tertiary" class="text-info btn-xs" wire:click="edit('{{ $registration->id }}')" tooltip="{{ __('ui::common.edit') }}" />
                                            <x-ui::button icon="tabler.trash" variant="tertiary" class="text-error btn-xs" wire:click="discard('{{ $registration->id }}')" tooltip="{{ __('ui::common.delete') }}" />
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="flex flex-col items-center justify-center py-12 text-center opacity-40">
                                            <x-ui::icon name="tabler.database-off" class="size-12 mb-2" />
                                            <p class="text-sm font-bold uppercase tracking-widest">{{ __('ui::common.no_results') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($this->records->hasPages())
                    <div class="mt-4 flex items-center justify-between px-2">
                        <div class="text-[10px] font-black uppercase tracking-widest opacity-40">
                            {{ __('ui::common.showing', ['from' => $this->records->firstItem(), 'to' => $this->records->lastItem(), 'total' => $this->records->total()]) }}
                        </div>
                        {{ $this->records->links('ui::components.pagination.tailwind') }}
                    </div>
                @endif
            </div>

            {{-- BULK PLACEMENT TAB --}}
            <div x-show="tab === 'bulk'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" role="tabpanel" class="max-w-4xl mx-auto space-y-8">
                
                {{-- Step 1 & 2 Wrapper --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    {{-- Target Selection --}}
                    <div class="space-y-6">
                        <div class="flex items-center gap-3">
                            <div class="size-8 rounded-xl bg-primary text-primary-content flex items-center justify-center font-black">1</div>
                            <h3 class="font-black uppercase tracking-widest text-sm">{{ __('internship::ui.select_target_location') }}</h3>
                        </div>

                        <div class="bg-base-200/50 rounded-2xl p-6 border border-base-content/5 space-y-4">
                            <x-ui::select :label="__('internship::ui.program')" icon="tabler.presentation" wire:model.live="internshipId" :options="$this->internships()" :placeholder="__('ui::common.select')" required />
                            <x-ui::select :label="__('internship::ui.placement')" icon="tabler.building" wire:model.live="companyId" :options="$this->companies()" :placeholder="__('ui::common.select')" :disabled="!$internshipId" required />

                            @if($internshipId && $companyId)
                                <div class="p-4 bg-base-100 rounded-xl border border-primary/20 flex items-center gap-4">
                                    <div class="radial-progress text-primary" style="--value:{{ (1 - ($this->remainingQuota() / 10)) * 100 }}; --size:3rem; --thickness: 4px;" role="progressbar">
                                        <span class="text-[10px] font-black">{{ $this->remainingQuota() }}</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-black uppercase opacity-40">{{ __('internship::ui.available_quota') }}</span>
                                        <span class="text-sm font-bold text-primary">{{ __('internship::ui.remaining_quota', ['count' => $this->remainingQuota()]) }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Student Selection --}}
                    <div class="space-y-6">
                        <div class="flex items-center gap-3">
                            <div class="size-8 rounded-xl bg-primary text-primary-content flex items-center justify-center font-black">2</div>
                            <h3 class="font-black uppercase tracking-widest text-sm">{{ __('internship::ui.select_students') }}</h3>
                        </div>

                        @if($internshipId)
                            <div class="bg-base-200/50 rounded-2xl p-6 border border-base-content/5 space-y-4">
                                @if($this->availableStudents())
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" class="checkbox checkbox-sm checkbox-primary" @change="$wire.selectedStudents = $event.target.checked ? $wire.availableStudents().map(s => s.id) : []" :checked="selectedStudents.length === availableStudents().length && availableStudents().length > 0" />
                                            <span class="text-[10px] font-black uppercase tracking-widest opacity-60">{{ __('internship::ui.select_all') }}</span>
                                        </div>
                                        <x-ui::badge :value="count($this->availableStudents())" variant="neutral" class="badge-sm" />
                                    </div>

                                    <div class="space-y-2 max-h-72 overflow-y-auto pr-2 custom-scrollbar">
                                        @foreach($this->availableStudents() as $student)
                                            <label class="flex items-center gap-3 p-3 rounded-xl bg-base-100 border border-transparent hover:border-primary/30 cursor-pointer transition-all group">
                                                <input type="checkbox" class="checkbox checkbox-sm checkbox-primary" wire:model.live="selectedStudents" value="{{ $student['id'] }}" />
                                                <div class="flex-1">
                                                    <p class="font-bold text-xs group-hover:text-primary transition-colors">{{ $student['name'] }}</p>
                                                    <p class="text-[9px] opacity-40 uppercase tracking-tighter">{{ $student['email'] }}</p>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="flex flex-col items-center justify-center py-8 text-center opacity-40">
                                        <x-ui::icon name="tabler.user-off" class="size-8 mb-2" />
                                        <p class="text-xs font-bold uppercase tracking-widest">{{ __('internship::ui.no_unplaced_students') }}</p>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center py-12 text-center opacity-20 border-2 border-dashed border-base-content/10 rounded-2xl">
                                <x-ui::icon name="tabler.arrow-big-left" class="size-12 mb-2 animate-bounce-horizontal" />
                                <p class="text-xs font-black uppercase tracking-widest">{{ __('internship::ui.select_program_first') }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Action Bar --}}
                @if($companyId && count($selectedStudents) > 0)
                    <div class="bg-primary text-primary-content rounded-2xl p-6 shadow-xl shadow-primary/20 flex flex-col md:flex-row items-center justify-between gap-6 animate-in slide-in-from-bottom-8 duration-500">
                        <div class="flex items-center gap-4">
                            <div class="size-12 rounded-xl bg-white/20 flex items-center justify-center">
                                <x-ui::icon name="tabler.user-check" class="size-6" />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs font-black uppercase tracking-widest opacity-70">{{ __('internship::ui.ready_to_place') }}</span>
                                <span class="text-xl font-bold">{{ count($selectedStudents) }} {{ __('internship::ui.students') }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 w-full md:w-auto">
                            <x-ui::button :label="__('ui::common.cancel')" variant="ghost" class="bg-white/10 hover:bg-white/20 border-none flex-1 md:flex-none" wire:click="resetBulkForm" />
                            <x-ui::button :label="__('internship::ui.execute_placement')" icon="tabler.rocket" class="bg-white text-primary border-none hover:bg-white/90 flex-1 md:flex-none" wire:click="showBulkConfirmation" />
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </x-ui::card>

    {{-- Form Modal (for individual registration editing) --}}
    <x-ui::modal wire:model="formModal" :title="$this->form->id ? __('internship::ui.edit_registration') : __('internship::ui.add_registration')">
        <x-ui::form wire:submit="save">
            <div class="space-y-6">
                <x-ui::select :label="__('internship::ui.program')" icon="tabler.book" wire:model.live="form.internship_id" :options="$this->internships()" :placeholder="__('ui::common.select')" required />
                <x-ui::select :label="__('internship::ui.student')" icon="tabler.user" wire:model="form.student_id" :options="$this->getStudents()" :placeholder="__('ui::common.select')" required />
                <x-ui::select :label="__('internship::ui.placement')" icon="tabler.building" wire:model="form.placement_id" :options="$this->getPlacements()" :placeholder="__('ui::common.select')" required />
                <x-ui::select :label="__('internship::ui.teacher')" icon="tabler.chalkboard" wire:model="form.teacher_id" :options="$this->getTeachers()" :placeholder="__('ui::common.select')" required />
                <x-ui::select :label="__('internship::ui.mentor')" icon="tabler.users-group" wire:model="form.mentor_id" :options="$this->getMentors()" :placeholder="__('ui::common.select')" />

                <div class="grid grid-cols-2 gap-4">
                    <x-ui::input type="date" :label="__('internship::ui.start_date')" icon="tabler.calendar-event" wire:model="form.start_date" required />
                    <x-ui::input type="date" :label="__('internship::ui.end_date')" icon="tabler.calendar-event" wire:model="form.end_date" required />
                </div>
            </div>

            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" x-on:click="$wire.formModal = false" />
                <x-ui::button :label="__('ui::common.save')" type="submit" variant="primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    {{-- Confirm Delete Modal --}}
    <x-ui::modal wire:model="confirmModal" :title="__('ui::common.confirm')">
        <div class="flex flex-col items-center text-center py-4">
            <div class="size-16 rounded-full bg-error/10 text-error flex items-center justify-center mb-4">
                <x-ui::icon name="tabler.trash-x" class="size-8" />
            </div>
            <h3 class="text-lg font-bold mb-2">{{ __('ui::common.are_you_sure') }}</h3>
            <p class="text-sm opacity-60 px-8">{{ $this->deleteConfirmMessage }}</p>
        </div>
        <x-slot:actions>
            <x-ui::button :label="__('ui::common.cancel')" x-on:click="$wire.confirmModal = false" class="flex-1" />
            <x-ui::button :label="__('ui::common.delete')" class="btn-error flex-1" wire:click="remove('{{ $this->recordId }}')" spinner="remove" />
        </x-slot:actions>
    </x-ui::modal>

    {{-- Bulk Placement Confirmation Modal --}}
    <x-ui::modal wire:model="bulkConfirmModal" :title="__('internship::ui.confirm_placement_title')">
        <div class="space-y-6 py-4">
            <div class="p-4 bg-info/10 text-info rounded-2xl flex items-start gap-4">
                <x-ui::icon name="tabler.info-circle" class="size-6 mt-1" />
                <p class="text-sm font-medium leading-relaxed">{{ __('internship::ui.confirm_placement_message') }}</p>
            </div>

            <div class="bg-base-200/50 rounded-2xl p-6 border border-base-content/5 space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-widest opacity-40">{{ __('internship::ui.placement_summary') }}</span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col">
                        <span class="text-lg font-bold">{{ count($selectedStudents) }}</span>
                        <span class="text-[9px] font-black uppercase opacity-40">{{ __('internship::ui.students_to_place') }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-lg font-bold">{{ $this->remainingQuota() }}</span>
                        <span class="text-[9px] font-black uppercase opacity-40">{{ __('internship::ui.available_quota') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <x-slot:actions>
            <x-ui::button :label="__('ui::common.cancel')" x-on:click="$wire.bulkConfirmModal = false" class="flex-1" />
            <x-ui::button :label="__('internship::ui.confirm_placement')" class="btn-success flex-1" wire:click="executeBulkPlacement" spinner="executeBulkPlacement" />
        </x-slot:actions>
    </x-ui::modal>
</div>
