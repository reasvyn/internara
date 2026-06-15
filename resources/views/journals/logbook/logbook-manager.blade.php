<div class="p-8">
    {{-- Header Section --}}
    <x-mary-header :title="__('logbook.title')" :subtitle="__('logbook.subtitle')" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button :label="__('logbook.new')" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-mary-header>

    {{-- Controls Section --}}
    <div class="mb-6 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
        <div class="w-full lg:max-w-md flex items-center gap-3">
            <x-mary-input 
                wire:model.live.debounce.300ms="search" 
                placeholder="{{ __('common.actions.search') }}" 
                icon="o-magnifying-glass" 
                clearable 
                class="rounded-2xl border-base-300 focus:border-primary transition-all duration-300 shadow-sm flex-1"
            />
            <x-mary-dropdown>
                <x-slot:trigger>
                    <x-mary-button icon="o-adjustments-horizontal" class="btn-ghost btn-sm" :label="__('common.actions.filters')" />
                </x-slot:trigger>
                <div class="p-4 space-y-4 w-72">
                    <x-mary-select
                        wire:model.live="filters.status"
                        :placeholder="__('logbook.status')"
                        :options="['draft' => 'Draft', 'submitted' => 'Submitted', 'verified' => 'Verified']"
                    />
                    <x-mary-select
                        wire:model.live="filters.is_verified"
                        :placeholder="__('logbook.verified')"
                        :options="['yes' => 'Verified', 'no' => 'Unverified']"
                    />
                </div>
            </x-mary-dropdown>
        </div>
    </div>

    {{-- Selection Bar --}}
    @if(count($this->selectedIds) > 0)
        <div class="mb-6 p-4 bg-primary/5 border border-primary/20 rounded-[2rem] flex flex-col sm:flex-row items-center justify-between gap-4 animate-in fade-in slide-in-from-top-2 duration-500 shadow-xl shadow-primary/5">
            <div class="flex items-center gap-4">
                <div class="size-12 rounded-2xl bg-primary text-primary-content flex items-center justify-center font-black shadow-lg shadow-primary/20">
                    {{ $this->selected_count }}
                </div>
                <div class="text-center sm:text-left">
                    <h4 class="font-black text-sm text-primary uppercase tracking-tight">{{ __('Records Selected') }}</h4>
                    <p class="text-[10px] uppercase font-black tracking-widest opacity-40">{{ __('Apply bulk operations') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex gap-2">
                    <x-mary-button 
                        :label="__('common.actions.delete_selected')" 
                        icon="o-trash" 
                        class="btn-sm btn-error text-white font-bold rounded-lg" 
                        wire:click="askDeleteSelected" 
                    />
                </div>
                <div class="divider divider-horizontal mx-1"></div>
                <x-mary-button 
                    label="{{ __('Cancel') }}" 
                    wire:click="clearSelection" 
                    class="btn-sm btn-ghost rounded-xl font-black uppercase tracking-widest text-[10px]" 
                />
            </div>
        </div>
    @endif

    {{-- Table Section --}}
    <x-mary-card shadow class="card-enterprise">
        <div class="table-enterprise">
            <x-mary-table 
                :headers="$this->headers()" 
                :rows="$this->rows()" 
                :sort-by="$sortBy"
                with-pagination 
                selectable
                wire:model="selectedIds"
                class="table-sm"
            >
                @scope('cell_user.name', $entry)
                    <div class="flex items-center gap-3 py-1">
                        <x-core::ui.avatar :user="$entry->user" size="size-9" />
                        <div class="flex flex-col">
                            <span class="font-bold text-sm">{{ $entry->user->name }}</span>
                            <span class="text-[10px] opacity-50 font-mono">{{ $entry->user->email }}</span>
                        </div>
                    </div>
                @endscope

                @scope('cell_date', $entry)
                    <span class="text-sm font-medium">{{ $entry->date->format('d M Y') }}</span>
                @endscope

                @scope('cell_content', $entry)
                    <div class="max-w-xs truncate text-sm text-base-content/70">
                        {{ $entry->content }}
                    </div>
                @endscope

                @scope('cell_status', $entry)
                    <x-mary-badge :value="__('logbook.statuses.' . $entry->status->value)" class="font-bold text-[10px] uppercase tracking-tighter 
                        {{ $entry->status->value === 'verified' ? 'badge-success' : ($entry->status->value === 'submitted' ? 'badge-info' : ($entry->status->value === 'revision_required' ? 'badge-warning' : 'badge-ghost')) }}" />
                @endscope

                @scope('cell_is_verified', $entry)
                    @if($entry->is_verified)
                        <x-mary-icon name="o-check-circle" class="size-5 text-success" />
                    @else
                        <x-mary-icon name="o-x-circle" class="size-5 text-base-content/30" />
                    @endif
                @endscope

                @scope('cell_supervisor_note', $entry)
                    @if($entry->supervisor_note)
                        <div class="max-w-xs truncate text-sm text-base-content/70">
                            {{ \Illuminate\Support\Str::limit($entry->supervisor_note, 60) }}
                        </div>
                    @else
                        <span class="text-xs text-base-content/30 italic">{{ __('logbook.no_supervisor_note') }}</span>
                    @endif
                @endscope

                @scope('actions', $entry)
                    <div class="flex justify-end gap-1">
                        @if(auth()->user()?->hasRole('supervisor'))
                            <x-mary-button icon="o-chat-bubble-bottom-center-text" class="btn-ghost btn-sm text-info" wire:click="editSupervisorNote('{{ $entry->id }}')" tooltip="{{ __('logbook.edit_supervisor_note') }}" />
                        @endif
                        <x-mary-button icon="o-check" class="btn-ghost btn-sm text-success" wire:click="verify('{{ $entry->id }}')" tooltip="Toggle Verify" />
                        <x-mary-button icon="o-pencil" class="btn-ghost btn-sm text-primary" wire:click="edit('{{ $entry->id }}')" tooltip="Edit" />
                        <x-mary-button icon="o-document-arrow-down" class="btn-ghost btn-sm text-secondary" :href="route('sysadmin.logbook.report', $entry->registration_id)" external tooltip="{{ __('logbook.download_report') }}" />
                        <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error"
                            wire:click="askDelete('{{ $entry->id }}')" tooltip="Delete" />
                    </div>
                @endscope
            </x-mary-table>
        </div>
    </x-mary-card>

    {{-- Supervisor Note Modal --}}
    <x-mary-modal wire:model="showSupervisorNoteModal" :title="__('logbook.edit_supervisor_note')" separator class="backdrop-blur-sm">
        <div class="space-y-6">
            <x-mary-textarea
                :label="__('logbook.supervisor_note')"
                wire:model="supervisorNote"
                :placeholder="__('logbook.supervisor_note_placeholder')"
                rows="4"
                class="rounded-xl border-base-300"
            />
        </div>
        <x-slot:actions>
            <x-mary-button :label="__('common.actions.cancel')" @click="$wire.showSupervisorNoteModal = false" class="rounded-xl" />
            <x-mary-button :label="__('logbook.save')" class="btn-primary rounded-xl font-bold uppercase tracking-widest" wire:click="saveSupervisorNote" spinner="saveSupervisorNote" />
        </x-slot:actions>
    </x-mary-modal>

    {{-- Form Modal --}}
    <x-mary-modal wire:model="showModal" :title="$formData['id'] ? __('logbook.edit') : __('logbook.new')" separator class="backdrop-blur-sm">
        <div class="space-y-6">
            @if(!$formData['id'])
                <x-mary-select :label="__('logbook.student')" wire:model="formData.user_id" :options="$this->students" placeholder="Select student..." class="rounded-xl border-base-300" />
            @endif

            <x-mary-datepicker :label="__('logbook.date')" wire:model="formData.date" icon="o-calendar" class="rounded-xl border-base-300" />

            <x-mary-textarea :label="__('logbook.content')" wire:model="formData.content" rows="4" class="rounded-xl border-base-300" />

            <x-mary-textarea :label="__('logbook.learning_outcomes')" wire:model="formData.learning_outcomes" rows="2" class="rounded-xl border-base-300" />

            <x-mary-textarea :label="__('logbook.mentor_feedback')" wire:model="formData.mentor_feedback" rows="2" class="rounded-xl border-base-300" />
        </div>

        <x-slot:actions>
            <x-mary-button :label="__('common.actions.cancel')" @click="$wire.showModal = false" class="rounded-xl" />
            <x-mary-button :label="__('logbook.save')" class="btn-primary rounded-xl font-bold uppercase tracking-widest" wire:click="save" spinner="save" />
        </x-slot:actions>
    </x-mary-modal>

    <x-core::ui.confirm :message="__('common.actions.confirm_action')" />
</div>
