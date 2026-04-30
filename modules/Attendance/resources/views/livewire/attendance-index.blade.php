<div>
    <x-ui::header 
        :title="__('attendance::ui.index.title')" 
        :subtitle="__('attendance::ui.index.subtitle')"
        :context="'attendance::ui.index.title'"
    >
        <x-slot:actions>
            @if(auth()->user()->hasRole('student'))
                <div class="flex gap-2">
                    <x-ui::button :label="__('attendance::ui.index.quick_check_in')" icon="tabler.check" wire:click="quickCheckIn" variant="primary" spinner="quickCheckIn" />
                    <x-ui::button :label="__('attendance::ui.index.fill_attendance')" icon="tabler.edit" wire:click="openAttendanceModal" variant="secondary" />
                </div>
            @endif
        </x-slot:actions>
    </x-ui::header>

    <div class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-4">
            @if(!auth()->user()->hasRole('student'))
                <x-ui::input icon="tabler.search" :placeholder="__('attendance::ui.index.search_student')" wire:model.live.debounce="search" />
            @endif
            <x-ui::input type="date" :label="__('attendance::ui.index.date_from')" wire:model.live="date_from" />
            <x-ui::input type="date" :label="__('attendance::ui.index.date_to')" wire:model.live="date_to" />
        </div>

        <x-ui::card>
            <x-ui::table :headers="[
                ['key' => 'date', 'label' => __('attendance::ui.index.table.date')],
                ['key' => 'student.name', 'label' => __('attendance::ui.index.table.student'), 'hidden' => auth()->user()->hasRole('student')],
                ['key' => 'check_in_at', 'label' => __('attendance::ui.index.table.check_in')],
                ['key' => 'check_out_at', 'label' => __('attendance::ui.index.table.check_out')],
                ['key' => 'status', 'label' => __('attendance::ui.index.table.status')],
                ['key' => 'notes', 'label' => __('attendance::ui.index.table.notes')],
            ]" :rows="$this->logs" with-pagination>
                @scope('cell_date', $log)
                    <div class="font-medium">{{ $log->date->translatedFormat('d F Y') }}</div>
                    <div class="text-[10px] uppercase tracking-wider opacity-50">{{ $log->date->translatedFormat('l') }}</div>
                @endscope

                @scope('cell_check_in_at', $log)
                    <span class="text-sm">{{ $log->check_in_at?->format('H:i') ?: '-' }}</span>
                @endscope

                @scope('cell_check_out_at', $log)
                    <span class="text-sm">{{ $log->check_out_at?->format('H:i') ?: '-' }}</span>
                @endscope

                @scope('cell_status', $log)
                    <x-ui::badge 
                        :value="$log->getStatusLabel()" 
                        :variant="$log->getStatusColor() === 'success' ? 'primary' : 'secondary'" 
                        class="badge-sm" 
                    />
                @endscope

                @scope('cell_notes', $log)
                    <span class="text-xs italic opacity-70">{{ $log->notes ?: '-' }}</span>
                @endscope
            </x-ui::table>
        </x-ui::card>

    <x-ui::modal wire:model="attendanceModal" :title="__('attendance::ui.index.modal.title')" separator>
        <x-ui::form wire:submit="submitAttendance">
            <x-ui::input type="date" :label="__('attendance::ui.index.modal.date')" wire:model="form_date" required />
            
            <x-ui::select 
                :label="__('attendance::ui.index.modal.status')" 
                wire:model="form_status" 
                :options="[
                    ['id' => 'present', 'name' => __('attendance::status.present')],
                    ['id' => 'sick', 'name' => __('attendance::status.sick')],
                    ['id' => 'permitted', 'name' => __('attendance::status.permitted')],
                    ['id' => 'unexplained', 'name' => __('attendance::status.unexplained')],
                ]" 
                required 
            />

            <x-ui::textarea :label="__('attendance::ui.index.modal.notes')" wire:model="form_notes" :placeholder="__('attendance::ui.index.modal.notes_placeholder')" />

            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" x-on:click="$wire.attendanceModal = false" />
                <x-ui::button :label="__('ui::common.save')" type="submit" variant="primary" spinner="submitAttendance" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>
</div>