<div class="p-8">
    {{-- Header Section --}}
    <x-mary-header :title="__('logbook.title')" :subtitle="__('logbook.subtitle')" separator progress-indicator>
        <x-slot:actions>
            <x-ts-button :text="__('logbook.new')" icon="plus" color="primary" wire:click="create" />
        </x-slot:actions>
    </x-mary-header>

    {{-- Controls Section --}}
    <div class="mb-6 flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-center">
        <div class="flex w-full items-center gap-3 lg:max-w-md">
            <x-ts-input
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('common.actions.search') }}"
                icon="magnifying-glass"
                clearable
                class="border-base-300 focus:border-primary flex-1 rounded-2xl shadow-sm transition-all duration-300"
            />
            <x-ts-dropdown>
                <x-slot:trigger>
                    <x-ts-button icon="adjustments-horizontal" color="white" sm :text="__('common.actions.filters')" />
                </x-slot:trigger>
                <div class="w-72 space-y-4 p-4">
                    <x-mary-select
                        wire:model.live="filters.status"
                        :placeholder="__('logbook.status')"
                        :options="['draft' => __('logbook.statuses.draft'), 'submitted' => __('logbook.statuses.submitted'), 'verified' => __('logbook.statuses.verified')]"
                    />
                    <x-mary-select
                        wire:model.live="filters.is_verified"
                        :placeholder="__('logbook.verified')"
                        :options="['yes' => __('logbook.verified'), 'no' => __('logbook.unverified')]"
                    />
                </div>
            </x-ts-dropdown>
        </div>
    </div>

    {{-- Selection Bar --}}
    @if (count($this->selectedIds) > 0)
        <div class="bg-primary/5 border-primary/20 animate-in fade-in slide-in-from-top-2 shadow-primary/5 mb-6 flex flex-col items-center justify-between gap-4 rounded-[2rem] border p-4 shadow-xl duration-500 sm:flex-row">
            <div class="flex items-center gap-4">
                <div class="bg-primary text-primary-content shadow-primary/20 flex size-12 items-center justify-center rounded-2xl font-black shadow-lg">
                    {{ $this->selected_count }}
                </div>
                <div class="text-center sm:text-left">
                    <h4 class="text-primary text-sm font-black tracking-tight uppercase">
                        {{ __('logbook.records_selected') }}
                    </h4>
                    <p class="text-[10px] font-black tracking-widest uppercase opacity-40">
                        {{ __('logbook.bulk_operations') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex gap-2">
                    <x-ts-button
                        :text="__('common.actions.delete_selected')"
                        icon="trash"
                        class="rounded-lg font-bold text-white"
                        color="red"
                        sm
                        wire:click="askDeleteSelected"
                    />
                </div>
                <div class="divider divider-horizontal mx-1"></div>
                <x-ts-button
                    text="{{ __('common.actions.cancel') }}"
                    wire:click="clearSelection"
                    class="rounded-xl text-[10px] font-black tracking-widest uppercase"
                    color="white"
                    sm
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
                            <span class="text-sm font-bold">{{ $entry->user->name }}</span>
                            <span class="font-mono text-[10px] opacity-50">{{ $entry->user->email }}</span>
                        </div>
                    </div>
                @endscope

                @scope('cell_date', $entry)
                    <span class="text-sm font-medium">{{ $entry->date->format('d M Y') }}</span>
                @endscope

                @scope('cell_content', $entry)
                    <div class="text-base-content/70 max-w-xs truncate text-sm">{{ $entry->content }}</div>
                @endscope

                @scope('cell_status', $entry)
                    <x-ts-badge
                        :text="__('logbook.statuses.'.$entry->status->value)"
                        class="font-bold text-[10px] uppercase tracking-tighter 
                        {{ $entry->status->value === 'verified' ? 'badge-success' : ($entry->status->value === 'submitted' ? 'badge-info' : ($entry->status->value === 'revision_required' ? 'badge-warning' : 'badge-ghost')) }}"
                    />
                @endscope

                @scope('cell_is_verified', $entry)
                    @if ($entry->is_verified)
                        <x-ts-icon name="check-circle" class="text-success size-5" />
                    @else
                        <x-ts-icon name="x-circle" class="text-base-content/30 size-5" />
                    @endif
                @endscope

                @scope('cell_supervisor_note', $entry)
                    @if ($entry->supervisor_note)
                        <div class="text-base-content/70 max-w-xs truncate text-sm">
                            {{ \Illuminate\Support\Str::limit($entry->supervisor_note, 60) }}
                        </div>
                    @else
                        <span class="text-base-content/60 text-xs italic">{{ __('logbook.no_supervisor_note') }}</span>
                    @endif
                @endscope

                @scope('actions', $entry)
                    <div class="flex justify-end gap-1">
                        @if (auth()->user()?->hasRole('supervisor'))
                            <x-ts-button
                                icon="chat-bubble-bottom-center-text"
                                class="text-info"
                                color="white"
                                sm
                                wire:click="editSupervisorNote('{{ $entry->id }}')"
                                tooltip="{{ __('logbook.edit_supervisor_note') }}"
                            />
                        @endif
                        <x-ts-button
                            icon="check"
                            class="text-success"
                            color="white"
                            sm
                            wire:click="verify('{{ $entry->id }}')"
                            tooltip="{{ __('logbook.toggle_verify') }}"
                        />
                        <x-ts-button
                            icon="pencil"
                            class="text-primary"
                            color="white"
                            sm
                            wire:click="edit('{{ $entry->id }}')"
                            tooltip="{{ __('common.actions.edit') }}"
                        />
                        <x-ts-button
                            icon="document-arrow-down"
                            class="text-secondary"
                            color="white"
                            sm
                            :href="route('sysadmin.logbook.report', $entry->registration_id)"
                            external
                            tooltip="{{ __('logbook.download_report') }}"
                        />
                        <x-ts-button
                            icon="trash"
                            class="text-error"
                            color="white"
                            sm
                            wire:click="askDelete('{{ $entry->id }}')"
                            tooltip="{{ __('common.actions.delete') }}"
                        />
                    </div>
                @endscope
            </x-mary-table>
        </div>
    </x-mary-card>

    {{-- Supervisor Note Modal --}}
    <x-ts-modal wire="showSupervisorNoteModal" :title="__('logbook.edit_supervisor_note')" separator blur>
        <div class="space-y-6">
            <x-ts-textarea
                :label="__('logbook.supervisor_note')"
                wire:model="supervisorNote"
                :placeholder="__('logbook.supervisor_note_placeholder')"
                rows="4"
                class="border-base-300 rounded-xl"
            />
        </div>
        <x-slot:footer>
            <x-ts-button
                :text="__('common.actions.cancel')"
                @click="$wire.showSupervisorNoteModal = false"
                class="rounded-xl"
            />
            <x-ts-button
                :text="__('logbook.save')"
                class="rounded-xl font-bold tracking-widest uppercase"
                color="primary"
                wire:click="saveSupervisorNote"
                loading="saveSupervisorNote"
            />
        </x-slot:footer>
    </x-ts-modal>

    {{-- Form Modal --}}
    <x-ts-modal wire="showModal" :title="$this->form->id ? __('logbook.edit') : __('logbook.new')" separator blur>
        <div class="space-y-6">
            @if (! $this->form->id)
                <x-mary-select
                    :label="__('logbook.student')"
                    wire:model="form.user_id"
                    :options="$this->students"
                    :placeholder="__('logbook.select_student')"
                    class="border-base-300 rounded-xl"
                />
            @endif

            <x-mary-datepicker
                :label="__('logbook.date')"
                wire:model="form.date"
                icon="calendar"
                class="border-base-300 rounded-xl"
            />

            <x-ts-textarea
                :label="__('logbook.content')"
                wire:model="form.content"
                rows="4"
                class="border-base-300 rounded-xl"
            />

            <x-ts-textarea
                :label="__('logbook.learning_outcomes')"
                wire:model="form.learning_outcomes"
                rows="2"
                class="border-base-300 rounded-xl"
            />

            <x-ts-textarea
                :label="__('logbook.mentor_feedback')"
                wire:model="form.mentor_feedback"
                rows="2"
                class="border-base-300 rounded-xl"
            />
        </div>

        <x-slot:footer>
            <x-ts-button :text="__('common.actions.cancel')" @click="$wire.showModal = false" class="rounded-xl" />
            <x-ts-button
                :text="__('logbook.save')"
                class="rounded-xl font-bold tracking-widest uppercase"
                color="primary"
                wire:click="save"
                loading="save"
            />
        </x-slot:footer>
    </x-ts-modal>
</div>
