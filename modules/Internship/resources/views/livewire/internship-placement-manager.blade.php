<div class="space-y-8">
    {{-- Executive Summary: Premium Stats Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
        <x-ui::stat 
            :title="__('internship::ui.stats.total_locations')" 
            :value="$this->stats['total_locations']" 
            icon="tabler.map-pins" 
            variant="metadata" 
            class="stat-enterprise" 
        />
        <x-ui::stat 
            :title="__('internship::ui.stats.total_quota')" 
            :value="$this->stats['total_quota']" 
            icon="tabler.users-group" 
            variant="info" 
            class="stat-enterprise" 
        />
        <x-ui::stat 
            :title="__('internship::ui.stats.filled_quota')" 
            :value="$this->stats['filled_quota']" 
            icon="tabler.user-check" 
            variant="success" 
            class="stat-enterprise" 
        />
        <x-ui::stat 
            :title="__('internship::ui.stats.utilization_rate')" 
            :value="$this->stats['utilization'] . '%'" 
            icon="tabler.chart-pie" 
            variant="primary" 
            class="stat-enterprise" 
        />
    </div>

    <x-ui::record-manager>
        {{-- 1. Customized Table Cells --}}
        <x-slot:tableCells>
            @scope('cell_company_name', $placement)
                <div class="flex flex-col min-w-[200px]">
                    <span class="font-bold text-sm text-base-content/90">{{ $placement->company_name }}</span>
                    <span class="text-[10px] opacity-40 uppercase tracking-widest font-black line-clamp-1">{{ $placement->id }}</span>
                </div>
            @endscope

            @scope('cell_quota', $placement)
                <div class="flex flex-col gap-1 min-w-[140px] py-1">
                    <div class="flex justify-between text-[9px] font-black uppercase tracking-tighter">
                        <span class="opacity-50">{{ $placement->capacity_quota - $placement->remaining_slots }} / {{ $placement->capacity_quota }}</span>
                        <span class="{{ $placement->utilization_percentage > 90 ? 'text-error' : 'text-primary' }}">{{ $placement->utilization_percentage }}%</span>
                    </div>
                    <div class="h-1.5 w-full bg-base-content/5 rounded-full overflow-hidden">
                        <div class="h-full {{ $placement->utilization_percentage > 90 ? 'bg-error' : 'bg-primary' }} transition-all duration-500" style="width: {{ $placement->utilization_percentage }}%"></div>
                    </div>
                </div>
            @endscope

            @scope('cell_mentor_name', $placement)
                <div class="flex items-center gap-2">
                    <div class="size-6 rounded-lg bg-base-200 flex items-center justify-center">
                        <x-ui::icon name="tabler.user-bolt" class="size-3 opacity-40" />
                    </div>
                    <span class="text-sm font-medium">{{ $placement->mentor_name }}</span>
                </div>
            @endscope
        </x-slot:tableCells>

        {{-- Row Actions --}}
        <x-slot:rowActions>
            @scope('actions', $placement)
                <div class="flex items-center justify-end gap-1 px-2">
                    @if($this->can('update', $placement))
                        <x-ui::button icon="tabler.edit" variant="tertiary" class="text-info/40 hover:text-info btn-xs" wire:click="edit('{{ $placement->id }}')" tooltip="{{ __('ui::common.edit') }}" />
                    @endif
                    @if($this->can('delete', $placement))
                        <x-ui::button icon="tabler.trash" variant="tertiary" class="text-error/40 hover:text-error btn-xs" wire:click="discard('{{ $placement->id }}')" tooltip="{{ __('ui::common.delete') }}" />
                    @endif
                </div>
            @endscope
        </x-slot:rowActions>

        {{-- 2. Form Fields --}}
        <x-slot:formFields>
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
                :label="__('internship::ui.company_name')" 
                icon="tabler.building"
                wire:model="form.company_id" 
                :options="$this->companies" 
                :placeholder="__('internship::ui.select_company')"
                required 
            />

            <x-ui::input :label="__('internship::ui.capacity_quota')" icon="tabler.users-group" type="number" wire:model="form.capacity_quota" required min="1" />

            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <x-ui::select 
                        :label="__('internship::ui.mentor')" 
                        icon="tabler.user-check"
                        wire:model="form.mentor_id" 
                        :options="$this->mentors" 
                        :placeholder="__('internship::ui.select_mentor')"
                    />
                </div>
                <x-ui::button icon="tabler.user-plus" variant="secondary" wire:click="addMentor" tooltip="{{ __('internship::ui.add_new_mentor') }}" />
            </div>
        </x-slot:formFields>
    </x-ui::record-manager>

    {{-- JIT Mentor Modal --}}
    <x-ui::modal wire:model="mentorModal" :title="__('internship::ui.add_new_mentor')">
        <x-ui::form wire:submit="saveMentor">
            <x-ui::input :label="__('ui::common.name')" icon="tabler.user" wire:model="mentorForm.name" required />
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui::input :label="__('ui::common.email')" icon="tabler.mail" type="email" wire:model="mentorForm.email" required />
                <x-ui::input :label="__('ui::common.username')" icon="tabler.id" wire:model="mentorForm.username" required />
            </div>
            <x-ui::input :label="__('ui::common.password')" icon="tabler.lock" type="password" wire:model="mentorForm.password" required />

            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" x-on:click="$wire.mentorModal = false" />
                <x-ui::button :label="__('ui::common.save')" type="submit" variant="primary" spinner="saveMentor" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>
</div>
