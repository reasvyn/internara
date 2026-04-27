<div class="space-y-8">
    {{-- Executive Summary: Premium Stats Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        <x-ui::stat 
            :title="__('internship::ui.stats.total_registrations')" 
            :value="$this->stats['total']" 
            icon="tabler.users" 
            variant="metadata" 
            class="stat-enterprise" 
        />
        <x-ui::stat 
            :title="__('internship::ui.stats.placed_students')" 
            :value="$this->stats['placed']" 
            icon="tabler.user-check" 
            variant="success" 
            class="stat-enterprise" 
        />
        <x-ui::stat 
            :title="__('internship::ui.stats.unplaced_students')" 
            :value="$this->stats['unplaced']" 
            icon="tabler.user-question" 
            variant="warning" 
            class="stat-enterprise" 
        />
        <x-ui::stat 
            :title="__('internship::ui.stats.new_registrations')" 
            :value="$this->stats['new']" 
            icon="tabler.sparkles" 
            variant="primary" 
            class="stat-enterprise" 
        />
    </div>

    {{-- Mode Switcher: Premium Segmented Navigation --}}
    <div class="inline-flex p-1.5 bg-base-100/50 backdrop-blur-md rounded-2xl border border-base-content/5 shadow-inner">
        <button 
            wire:click="setTab('individual')"
            @class([
                'flex items-center gap-2.5 px-6 py-2.5 rounded-xl text-[11px] font-black uppercase tracking-[0.15em] transition-all duration-300',
                'bg-primary text-primary-content shadow-lg shadow-primary/20 scale-100' => $activeTab === 'individual',
                'text-base-content/40 hover:text-base-content/70 hover:bg-base-content/5 scale-95' => $activeTab !== 'individual',
            ])
        >
            <x-ui::icon name="tabler.user-check" class="size-4" />
            <span>{{ __('internship::ui.individual_placement') }}</span>
        </button>
        <button 
            wire:click="setTab('bulk')"
            @class([
                'flex items-center gap-2.5 px-6 py-2.5 rounded-xl text-[11px] font-black uppercase tracking-[0.15em] transition-all duration-300 relative',
                'bg-primary text-primary-content shadow-lg shadow-primary/20 scale-100' => $activeTab === 'bulk',
                'text-base-content/40 hover:text-base-content/70 hover:bg-base-content/5 scale-95' => $activeTab !== 'bulk',
            ])
        >
            <x-ui::icon name="tabler.users-group" class="size-4" />
            <span>{{ __('internship::ui.bulk_placement') }}</span>
            @if($this->stats['unplaced'] > 0)
                <span class="absolute -top-1 -right-1 flex size-5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-warning opacity-75"></span>
                    <span class="relative inline-flex rounded-full size-5 bg-warning text-warning-content text-[9px] font-black items-center justify-center shadow-sm">
                        {{ $this->stats['unplaced'] }}
                    </span>
                </span>
            @endif
        </button>
    </div>

    {{-- INDIVIDUAL PLACEMENT MODE --}}
    @if($activeTab === 'individual')
        <div x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
            <x-ui::record-manager>
                <x-slot:tableCells>
                    @scope('cell_student_name', $registration)
                        <div class="flex items-center gap-3">
                            <x-ui::avatar :src="$registration->student_avatar" :title="$registration->student_name" class="rounded-xl size-10 shadow-sm" />
                            <div class="flex flex-col">
                                <span class="font-bold text-sm text-base-content/90 tracking-tight">{{ $registration->student_name }}</span>
                                <span class="text-[9px] opacity-40 uppercase tracking-widest font-black">{{ __('internship::ui.teacher') }}: {{ $registration->teacher_name }}</span>
                            </div>
                        </div>
                    @endscope

                    @scope('cell_internship_title', $registration)
                        <div class="flex flex-col">
                            <span class="text-sm font-medium opacity-80">{{ $registration->internship_title }}</span>
                        </div>
                    @endscope

                    @scope('cell_placement_company', $registration)
                        @if($registration->placement_company !== '-')
                            <div class="flex items-center gap-2 py-1 px-3 bg-primary/5 rounded-lg border border-primary/10 w-fit">
                                <x-ui::icon name="tabler.building" class="size-3.5 text-primary" />
                                <span class="text-xs font-bold text-primary tracking-tight">{{ $registration->placement_company }}</span>
                            </div>
                        @elseif($registration->proposed_company_name)
                            <div class="flex flex-col gap-1.5">
                                <x-ui::badge value="{{ __('internship::ui.propose_new_partner') }}" variant="warning" class="badge-xs font-black text-[8px] uppercase tracking-widest" />
                                <span class="text-[11px] italic font-medium opacity-50 line-clamp-1 border-l-2 border-warning/30 pl-2 ml-1">{{ $registration->proposed_company_name }}</span>
                            </div>
                        @else
                            <div class="flex items-center gap-2 opacity-30 italic">
                                <x-ui::icon name="tabler.help-circle" class="size-3.5" />
                                <span class="text-[11px] font-medium">{{ __('internship::ui.not_placed') }}</span>
                            </div>
                        @endif
                    @endscope

                    @scope('cell_status', $registration)
                        <x-ui::badge 
                            :value="$registration->status" 
                            :variant="match($registration->status) {
                                'approved' => 'success',
                                'pending' => 'warning',
                                'rejected' => 'error',
                                default => 'neutral'
                            }"
                            class="badge-sm font-black text-[9px] uppercase tracking-widest rounded-lg shadow-sm"
                        />
                    @endscope
                </x-slot:tableCells>

                {{-- Row Actions --}}
                <x-slot:rowActions>
                    @scope('actions', $registration)
                        <div class="flex items-center justify-end gap-1 px-2">
                            @if($this->can('update', $registration))
                                <x-ui::button icon="tabler.edit" variant="tertiary" class="text-info/40 hover:text-info btn-xs" wire:click="edit('{{ $registration->id }}')" tooltip="{{ __('ui::common.edit') }}" />
                            @endif
                            @if($this->can('delete', $registration))
                                <x-ui::button icon="tabler.trash" variant="tertiary" class="text-error/40 hover:text-error btn-xs" wire:click="discard('{{ $registration->id }}')" tooltip="{{ __('ui::common.delete') }}" />
                            @endif
                        </div>
                    @endscope
                </x-slot:rowActions>

                <x-slot:formFields>
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-x-10 gap-y-6 p-4">
                        <div class="space-y-6">
                            <x-ui::select :label="__('internship::ui.program')" icon="tabler.presentation" wire:model.live="form.internship_id" :options="$this->internships" :placeholder="__('ui::common.select')" required />
                            <x-ui::select :label="__('internship::ui.student')" icon="tabler.user" wire:model="form.student_id" :options="$this->students" :placeholder="__('ui::common.select')" required />
                            <x-ui::select :label="__('internship::ui.teacher')" icon="tabler.chalkboard" wire:model="form.teacher_id" :options="$this->teachers" :placeholder="__('ui::common.select')" required />
                        </div>
                        <div class="space-y-6">
                            <x-ui::select :label="__('internship::ui.placement')" icon="tabler.building" wire:model="form.placement_id" :options="$this->placements" :placeholder="__('ui::common.select')" required />
                            <x-ui::select :label="__('internship::ui.mentor')" icon="tabler.user-check" wire:model="form.mentor_id" :options="$this->mentors" :placeholder="__('ui::common.select')" />
                            
                            <div class="space-y-6">
                                <x-ui::input type="date" :label="__('internship::ui.start_date')" icon="tabler.calendar-plus" wire:model="form.start_date" required />
                                <x-ui::input type="date" :label="__('internship::ui.end_date')" icon="tabler.calendar-check" wire:model="form.end_date" required />
                            </div>
                        </div>
                    </div>
                </x-slot:formFields>
            </x-ui::record-manager>
        </div>
    @endif

    {{-- BULK PLACEMENT MODE --}}
    @if($activeTab === 'bulk')
        <div x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="max-w-6xl mx-auto">
            <x-ui::card class="card-enterprise border-none shadow-2xl overflow-hidden">
                <div class="flex flex-col lg:flex-row min-h-[600px]">
                    {{-- 1. Configuration Sidebar --}}
                    <div class="w-full lg:w-96 bg-base-200/40 p-10 border-r border-base-content/5 space-y-10">
                        <div class="space-y-2">
                            <div class="flex items-center gap-3">
                                <div class="size-7 rounded-lg bg-primary text-primary-content flex items-center justify-center text-[10px] font-black">01</div>
                                <h3 class="font-black uppercase tracking-[0.2em] text-[10px] text-base-content/50">{{ __('internship::ui.select_target_location') }}</h3>
                            </div>
                            <p class="text-[11px] font-medium opacity-40 leading-relaxed">{{ __('internship::ui.bulk_placement_note') }}</p>
                        </div>

                        <div class="space-y-8">
                            <x-ui::select :label="__('internship::ui.program')" icon="tabler.presentation" wire:model.live="internshipId" :options="$this->internships" :placeholder="__('ui::common.select')" required />
                            <x-ui::select :label="__('internship::ui.placement')" icon="tabler.building" wire:model.live="companyId" :options="$this->placements" :placeholder="__('ui::common.select')" :disabled="!$internshipId" required />

                            @if($internshipId && $companyId)
                                <div class="p-6 bg-base-100 rounded-3xl border border-primary/10 shadow-sm flex flex-col items-center text-center gap-3 animate-in fade-in slide-in-from-left-4 duration-500">
                                    <div class="radial-progress text-primary shadow-[0_0_15px_rgba(var(--p),0.2)]" style="--value:{{ (1 - ($this->remainingQuota / 10)) * 100 }}; --size:5rem; --thickness: 6px;" role="progressbar">
                                        <span class="text-lg font-black tracking-tighter">{{ $this->remainingQuota }}</span>
                                    </div>
                                    <div class="space-y-0.5">
                                        <span class="text-[9px] font-black uppercase tracking-widest opacity-40 block">{{ __('internship::ui.available_quota') }}</span>
                                        <span class="text-xs font-bold text-primary">{{ __('internship::ui.remaining_quota', ['count' => $this->remainingQuota]) }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- 2. Student Selection Canvas --}}
                    <div class="flex-1 p-10 flex flex-col">
                        <div class="flex items-center justify-between mb-10">
                            <div class="flex items-center gap-3">
                                <div class="size-7 rounded-lg bg-primary text-primary-content flex items-center justify-center text-[10px] font-black">02</div>
                                <h3 class="font-black uppercase tracking-[0.2em] text-[10px] text-base-content/50">{{ __('internship::ui.select_students') }}</h3>
                            </div>
                            
                            @if($internshipId && count($this->students) > 0)
                                <div class="flex items-center gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input type="checkbox" class="checkbox checkbox-xs checkbox-primary rounded-md" @change="$wire.selectedStudents = $event.target.checked ? $wire.students.map(s => s.id) : []" :checked="selectedStudents.length === students.length" />
                                        <span class="text-[10px] font-black uppercase tracking-widest text-base-content/40 group-hover:text-primary transition-colors">{{ __('internship::ui.select_all') }}</span>
                                    </label>
                                    <x-ui::badge :value="count($this->students)" variant="neutral" class="badge-sm font-black" />
                                </div>
                            @endif
                        </div>

                        <div class="flex-1">
                            @if($internshipId)
                                @if(count($this->students) > 0)
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        @foreach($this->students as $student)
                                            <label class="flex items-center gap-4 p-5 rounded-2xl bg-base-200/50 border border-transparent hover:border-primary/20 hover:bg-base-100 cursor-pointer transition-all duration-300 group">
                                                <input type="checkbox" class="checkbox checkbox-sm checkbox-primary rounded-lg" wire:model.live="selectedStudents" value="{{ $student['id'] }}" />
                                                <div class="flex-1 min-w-0">
                                                    <p class="font-bold text-sm tracking-tight group-hover:text-primary transition-colors truncate">{{ $student['name'] }}</p>
                                                    <p class="text-[10px] opacity-40 font-medium truncate">{{ $student['email'] }}</p>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="flex flex-col items-center justify-center h-full py-12 text-center">
                                        <div class="size-20 bg-base-200 rounded-full flex items-center justify-center mb-6 opacity-30">
                                            <x-ui::icon name="tabler.user-off" class="size-10" />
                                        </div>
                                        <p class="text-xs font-black uppercase tracking-[0.2em] text-base-content/30">{{ __('internship::ui.no_unplaced_students') }}</p>
                                    </div>
                                @endif
                            @else
                                <div class="flex flex-col items-center justify-center h-full py-12 text-center group">
                                    <div class="size-24 bg-primary/5 rounded-[2.5rem] flex items-center justify-center mb-8 border border-primary/10 group-hover:scale-110 transition-transform duration-700">
                                        <x-ui::icon name="tabler.arrow-big-left-filled" class="size-12 text-primary/20 animate-pulse" />
                                    </div>
                                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-primary/40 leading-relaxed">{{ __('internship::ui.select_program_first') }}</p>
                                </div>
                            @endif
                        </div>

                        {{-- Floating Action Bar --}}
                        @if($companyId && count($selectedStudents) > 0)
                            <div class="mt-10 bg-primary text-primary-content rounded-3xl p-6 shadow-2xl shadow-primary/30 flex items-center justify-between gap-6 animate-in slide-in-from-bottom-12 duration-700">
                                <div class="flex items-center gap-5">
                                    <div class="size-14 rounded-[1.25rem] bg-white/20 flex items-center justify-center shadow-inner">
                                        <x-ui::icon name="tabler.user-check" class="size-7" />
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-black uppercase tracking-widest opacity-60 leading-none mb-1.5">{{ __('internship::ui.ready_to_place') }}</span>
                                        <span class="text-2xl font-black tracking-tighter">{{ count($selectedStudents) }} <span class="text-xs font-bold opacity-80 ml-1">{{ __('internship::ui.students') }}</span></span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <x-ui::button :label="__('ui::common.reset')" variant="ghost" class="bg-white/10 hover:bg-white/20 border-none px-6 font-bold" wire:click="resetBulkForm" />
                                    <x-ui::button :label="__('internship::ui.execute_placement')" icon="tabler.rocket" class="bg-white text-primary border-none hover:bg-white/90 px-8 font-black uppercase tracking-widest text-[10px] shadow-lg" wire:click="showBulkConfirmation" />
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </x-ui::card>
        </div>
    @endif

    {{-- Bulk Placement Confirmation Modal --}}
    <x-ui::modal wire:model="bulkConfirmModal" :title="__('internship::ui.confirm_placement_title')">
        <div class="space-y-8 py-6">
            <div class="p-6 bg-info/5 text-info rounded-[2rem] border border-info/10 flex items-start gap-5 shadow-sm">
                <div class="size-10 rounded-2xl bg-info/20 flex items-center justify-center shrink-0">
                    <x-ui::icon name="tabler.info-circle" class="size-6" />
                </div>
                <p class="text-[13px] font-semibold leading-relaxed">{{ __('internship::ui.confirm_placement_message') }}</p>
            </div>

            <div class="bg-base-200/50 rounded-[2.5rem] p-8 border border-base-content/5 space-y-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-base-content/30">{{ __('internship::ui.placement_summary') }}</span>
                </div>
                <div class="grid grid-cols-2 gap-8 relative">
                    <div class="absolute inset-y-0 left-1/2 w-px bg-base-content/5 -translate-x-1/2"></div>
                    <div class="flex flex-col items-center text-center gap-1">
                        <span class="text-4xl font-black tracking-tighter text-primary">{{ count($selectedStudents) }}</span>
                        <span class="text-[9px] font-black uppercase tracking-widest opacity-40">{{ __('internship::ui.students_to_place') }}</span>
                    </div>
                    <div class="flex flex-col items-center text-center gap-1">
                        <span class="text-4xl font-black tracking-tighter">{{ $this->remainingQuota }}</span>
                        <span class="text-[9px] font-black uppercase tracking-widest opacity-40">{{ __('internship::ui.available_quota') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <x-slot:actions>
            <x-ui::button :label="__('ui::common.cancel')" x-on:click="$wire.bulkConfirmModal = false" class="flex-1 py-4 font-bold rounded-2xl" />
            <x-ui::button :label="__('internship::ui.confirm_placement')" class="btn-success flex-1 py-4 font-black uppercase tracking-widest text-[11px] rounded-2xl shadow-lg" wire:click="executeBulkPlacement" spinner="executeBulkPlacement" />
        </x-slot:actions>
    </x-ui::modal>
</div>
