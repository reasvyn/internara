<div>
    <x-ui::header 
        :title="__('teacher::ui.dashboard.title')" 
        :subtitle="__('teacher::ui.dashboard.subtitle')" 
        :context="'admin::ui.menu.dashboard'"
    />

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 mb-8">
        <x-ui::card class="bg-primary text-primary-content">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm opacity-80">{{ __('teacher::ui.dashboard.total_students') }}</div>
                    <div class="text-3xl font-bold">{{ $this->students->count() }}</div>
                </div>
                <x-ui::icon name="tabler.users" class="size-10 opacity-20" />
            </div>
        </x-ui::card>
    </div>

    <x-ui::card :title="__('teacher::ui.dashboard.assigned_students')">
        <x-ui::table :headers="[
            ['key' => 'student.name', 'label' => __('teacher::ui.dashboard.table.student_name')],
            ['key' => 'placement.company_name', 'label' => __('teacher::ui.dashboard.table.placement')],
            ['key' => 'status', 'label' => __('teacher::ui.dashboard.table.status')],
            ['key' => 'readiness', 'label' => __('teacher::ui.dashboard.table.readiness'), 'sortable' => false],
        ]" :rows="$this->students">
            @scope('cell_status', $registration)
                <x-ui::badge 
                    :value="$registration->getStatusLabel()" 
                    :variant="$registration->getStatusColor() === 'success' ? 'primary' : 'secondary'" 
                />
            @endscope

            @scope('cell_readiness', $registration)
                @php $readiness = $this->getReadiness($registration->id); @endphp
                @if($readiness['is_ready'])
                    <x-ui::badge :value="__('teacher::ui.dashboard.readiness.ready')" variant="primary" />
                @else
                    <div class="tooltip" data-tip="{{ implode(', ', $readiness['missing']) }}">
                        <x-ui::badge :value="__('teacher::ui.dashboard.readiness.not_ready')" variant="secondary" />
                    </div>
                @endif
            @endscope
            
            @scope('actions', $registration)
                <div class="flex gap-1">
                    <x-ui::button 
                        :label="__('teacher::ui.dashboard.actions.supervise')" 
                        icon="tabler.messages" 
                        variant="tertiary" 
                        class="text-primary btn-sm" 
                        link="{{ route('teacher.mentoring', $registration->id) }}" 
                    />
                    <x-ui::button 
                        :label="__('teacher::ui.dashboard.actions.assess')" 
                        icon="tabler.clipboard-check" 
                        variant="tertiary" 
                        class="btn-sm" 
                        link="{{ route('teacher.assess', $registration->id) }}" 
                    />
                    
                    @php $readiness = $this->getReadiness($registration->id); @endphp
                    @if($readiness['is_ready'])
                        <x-ui::button 
                            :label="__('teacher::ui.dashboard.actions.transcript')" 
                            icon="tabler.file-download" 
                            variant="tertiary" 
                            class="text-success btn-sm" 
                            link="{{ route('assessment.transcript', $registration->id) }}" 
                            external 
                        />
                    @endif
                </div>
            @endscope
        </x-ui::table>
    </x-ui::card>
</div>
