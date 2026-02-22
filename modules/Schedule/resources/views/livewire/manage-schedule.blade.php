<div>
    <x-ui::header 
        :title="__('schedule::ui.manage_title')" 
        :subtitle="__('schedule::ui.manage_subtitle')"
        :context="'schedule::ui.manage_title'"
    >
        <x-slot:actions>
            <x-ui::button 
                label="{{ __('schedule::ui.add_schedule') }}" 
                icon="tabler.plus" 
                class="btn-primary" 
                wire:click="create" 
                aria-label="{{ __('schedule::ui.add_schedule') }}"
            />
        </x-slot:actions>
    </x-ui::header>

    <x-ui::card shadow>
        <div class="flex flex-col md:flex-row gap-4 mb-6">
            <div class="flex-1">
                <x-ui::input 
                    icon="tabler.search" 
                    placeholder="{{ __('schedule::ui.search_placeholder') }}" 
                    wire:model.live.debounce.300ms="search" 
                    aria-label="{{ __('schedule::ui.search_placeholder') }}"
                />
            </div>
        </div>

        <x-ui::table :headers="$headers" :rows="$schedules" with-pagination>
            @scope('cell_type', $schedule)
                <span @class([
                    'badge badge-sm font-bold',
                    'badge-primary' => $schedule->type === 'briefing',
                    'badge-info' => $schedule->type === 'event',
                    'badge-error' => $schedule->type === 'deadline',
                ])>
                    {{ strtoupper($schedule->type) }}
                </span>
            @endscope

            @scope('cell_start_at', $schedule)
                <div class="text-sm">
                    <div class="font-bold">
                        <time datetime="{{ $schedule->start_at->toIso8601String() }}">
                            {{ $schedule->start_at->translatedFormat('d M Y') }}
                        </time>
                    </div>
                    <div class="opacity-50 text-xs">{{ $schedule->start_at->format('H:i') }}</div>
                </div>
            @endscope

            @scope('actions', $schedule)
                <div class="flex items-center gap-2">
                    <x-ui::button 
                        icon="tabler.edit" 
                        class="btn-ghost btn-sm btn-circle" 
                        wire:click="edit('{{ $schedule->id }}')" 
                        aria-label="{{ __('schedule::ui.edit_schedule') }}: {{ $schedule->title }}"
                    />
                    <x-ui::button 
                        icon="tabler.trash" 
                        class="btn-ghost btn-sm btn-circle text-error" 
                        wire:confirm="{{ __('schedule::ui.delete_confirm') }}"
                        wire:click="delete('{{ $schedule->id }}')" 
                        aria-label="{{ __('shared::actions.delete') }}: {{ $schedule->title }}"
                    />
                </div>
            @endscope
        </x-ui::table>
    </x-ui::card>

    {{-- Schedule Form Modal --}}
    <x-ui::modal 
        wire:model="showForm" 
        title="{{ $selectedScheduleId ? __('schedule::ui.edit_schedule') : __('schedule::ui.new_schedule') }}" 
        separator
    >
        <livewire:schedule::schedule-form :schedule-id="$selectedScheduleId" wire:key="{{ $selectedScheduleId ?? 'new' }}" />
    </x-ui::modal>
</div>
