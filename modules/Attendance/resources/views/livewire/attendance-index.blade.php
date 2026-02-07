<div>
    <x-ui::header :title="__('attendance::ui.index.title')" :subtitle="__('attendance::ui.index.subtitle')">
        <x-slot:actions>
            @if(auth()->user()->hasRole('student'))
                <x-ui::button :label="__('attendance::ui.index.request_absence')" icon="tabler.user-off" wire:click="openAbsenceModal" priority="secondary" />
            @endif
        </x-slot:actions>
    </x-ui::header>

    <x-ui::main>
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
                        :priority="$log->getStatusColor() === 'success' ? 'primary' : 'secondary'" 
                        class="badge-sm" 
                    />
                @endscope
            </x-ui::table>
        </x-ui::card>
    </x-ui::main>

    <x-ui::modal wire:model="absenceModal" :title="__('attendance::ui.index.modal.title')" separator>
        <x-ui::form wire:submit="submitAbsence">
            <x-ui::input type="date" :label="__('attendance::ui.index.modal.date')" wire:model="absence_date" required />
            
            <x-ui::select 
                :label="__('attendance::ui.index.modal.type')" 
                wire:model="absence_type" 
                :options="[
                    ['id' => 'leave', 'name' => __('attendance::ui.index.modal.types.leave')],
                    ['id' => 'sick', 'name' => __('attendance::ui.index.modal.types.sick')],
                    ['id' => 'permit', 'name' => __('attendance::ui.index.modal.types.permit')],
                ]" 
                required 
            />

            <x-ui::textarea :label="__('attendance::ui.index.modal.reason')" wire:model="absence_reason" :placeholder="__('attendance::ui.index.modal.reason_placeholder')" required />

            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" x-on:click="$wire.absenceModal = false" />
                <x-ui::button :label="__('attendance::ui.index.modal.submit')" type="submit" priority="primary" spinner="submitAbsence" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>
</div>