<div>
    <x-ui::header 
        :title="__('mentor::ui.dashboard.title')" 
        :subtitle="__('mentor::ui.dashboard.subtitle')" 
    />

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 mb-8">
        <x-ui::card class="bg-secondary text-secondary-content">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm opacity-80">{{ __('mentor::ui.dashboard.total_interns') }}</div>
                    <div class="text-3xl font-bold">{{ $this->students->count() }}</div>
                </div>
                <x-ui::icon name="tabler.school" class="size-10 opacity-20" />
            </div>
        </x-ui::card>
    </div>

    <x-ui::card :title="__('mentor::ui.dashboard.assigned_interns')">
        <x-ui::table :headers="[
            ['key' => 'student.name', 'label' => __('mentor::ui.dashboard.table.student_name')],
            ['key' => 'internship.title', 'label' => __('mentor::ui.dashboard.table.program')],
            ['key' => 'status', 'label' => __('mentor::ui.dashboard.table.status')],
        ]" :rows="$this->students">
            @scope('cell_status', $registration)
                <x-ui::badge 
                    :value="$registration->getStatusLabel()" 
                    :variant="$registration->getStatusColor() === 'success' ? 'primary' : 'secondary'" 
                />
            @endscope
            
            @scope('actions', $registration)
                <div class="flex gap-1">
                    <x-ui::button 
                        :label="__('mentor::ui.dashboard.actions.mentoring')" 
                        icon="tabler.messages" 
                        variant="tertiary" 
                        class="text-secondary btn-sm" 
                        link="{{ route('mentor.mentoring', $registration->id) }}" 
                    />
                    <x-ui::button 
                        :label="__('mentor::ui.dashboard.actions.evaluate')" 
                        icon="tabler.clipboard-check" 
                        variant="tertiary" 
                        class="btn-sm" 
                        link="{{ route('mentor.evaluate', $registration->id) }}" 
                    />
                </div>
            @endscope
        </x-ui::table>
    </x-ui::card>
</div>
