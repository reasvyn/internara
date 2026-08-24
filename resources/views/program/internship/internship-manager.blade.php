<x-core::ui.record-manager :title="__('internship.title')" :subtitle="__('internship.subtitle')">
    <x-slot:headerActions>
        <x-ts-button :text="__('internship.create_batch')" icon="plus" color="primary" sm wire:click="create" />
    </x-slot:headerActions>

    <x-slot:extraMenu>
        <x-ts-dropdown.items
            :text="__('common.actions.import')"
            icon="arrow-up-tray"
            @click="document.getElementById('import-csv').click()"
        />
        <input id="import-csv" type="file" accept=".csv" wire:model="importFile" class="hidden" />
        <x-ts-dropdown.items :text="__('common.actions.export')" icon="arrow-down-tray" wire:click="export" />
        <x-ts-dropdown.items
            :text="__('common.actions.template')"
            icon="document-arrow-down"
            wire:click="downloadTemplate"
        />
    </x-slot:extraMenu>

    <x-slot:stats>
        <x-core::widgets.stat-card
            icon="calendar"
            :title="__('internship.stats.total')"
            :value="$this->stats['total']"
        />
        <x-core::widgets.stat-card icon="play" :title="__('internship.stats.active')" :value="$this->stats['active']" />
        <x-core::widgets.stat-card
            icon="briefcase"
            :title="__('internship.stats.total_placements')"
            :value="$this->stats['total_placements']"
        />
        <x-core::widgets.stat-card
            icon="user-group"
            :title="__('internship.stats.total_registrations')"
            :value="$this->stats['total_registrations']"
        />
    </x-slot:stats>

    <x-slot:filters>
        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('internship.status') }}</label>
        <select wire:model.live="filters.status" class="select select-bordered select-sm w-full text-sm">
            <option value="">{{ __('internship.all_statuses') }}</option>
            @foreach ($this->statusOptions as $opt)
                <option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>
            @endforeach
        </select>

        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('internship.filter_academic_year') }}</label>
        <select wire:model.live="filters.academic_year_id" class="select select-bordered select-sm w-full text-sm">
            <option value="">{{ __('internship.select_academic_year') }}</option>
            @foreach ($this->academicYears as $year)
                <option value="{{ $year['id'] }}">{{ $year['name'] }}</option>
            @endforeach
        </select>

        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('internship.filter_date_from') }}</label>
        <input wire:model.live="filters.date_from" type="date" class="input input-bordered input-sm w-full text-sm" />

        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('internship.filter_date_to') }}</label>
        <input wire:model.live="filters.date_to" type="date" class="input input-bordered input-sm w-full text-sm" />
    </x-slot:filters>

    <x-core::ui.selection-bar>
        <x-ts-dropdown>
            <x-slot:trigger>
                <x-ts-button
                    icon="chevron-down"
                    class="font-medium"
                    color="primary"
                    sm
                    :text="__('common.actions.bulk_actions')"
                />
            </x-slot:trigger>
            <div class="w-48 p-1.5">
                <x-ts-dropdown.items
                    :text="__('common.actions.export_selected')"
                    icon="arrow-down-tray"
                    wire:click="exportSelected"
                />
                <hr class="border-base-content/10" />
                <x-ts-dropdown.items
                    :text="__('common.actions.delete_selected')"
                    icon="trash"
                    class="text-error"
                    wire:click="askDeleteSelected"
                />
                <x-ts-dropdown.items
                    :text="__('internship.complete_filtered')"
                    icon="check-circle"
                    wire:click="askCloseFiltered"
                />
            </div>
        </x-ts-dropdown>
    </x-core::ui.selection-bar>

    <div class="overflow-x-auto">
        <x-ts-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            selectable
            wire:model="selectedIds"
            class="table-sm"
        >
            @interact('column_start_date', $internship)
                <span class="text-sm font-medium">{{ $internship->start_date->format('d M Y') }}</span>
            @endinteract

            @interact('column_end_date', $internship)
                <span class="text-sm font-medium">{{ $internship->end_date->format('d M Y') }}</span>
            @endinteract

            @interact('column_status', $internship)
                @php
                    $statusClass = match ($internship->status->value) {
                        'active' => 'badge-success',
                        'published' => 'badge-info',
                        'completed' => 'badge-neutral',
                        'cancelled' => 'badge-error',
                        default => 'badge-ghost',
                    };
                @endphp
                <x-ts-badge
                    :text="__('internship.statuses.'.$internship->status->value)"
                    class="{{ $statusClass }} font-bold text-[10px] uppercase tracking-tighter"
                />
            @endinteract

            @interact('column_action', $internship)
                <div class="flex justify-end gap-1">
                    <x-ts-button
                        aria-label="{{ __('common.actions.edit') }}"
                        icon="pencil"
                        color="white"
                        sm
                        wire:click="edit('{{ $internship->id }}')"
                    />
                    <x-ts-button
                        aria-label="{{ __('common.actions.delete') }}"
                        icon="trash"
                        class="text-error"
                        color="white"
                        sm
                        wire:click="askDelete('{{ $internship->id }}')"
                    />
                </div>
            @endinteract
        </x-ts-table>
    </div>

    {{-- Confirm Dialog --}}
    <x-slot:modal>
        <x-ts-modal
            wire="showModal"
            :title="$form->id ? __('internship.edit_batch') : __('internship.new_batch')"
            separator
            blur
        >
            <div class="space-y-5">
                <div class="bg-base-200/30 border-base-content/10 rounded-xl border p-5">
                    <p class="text-base-content/50 mb-4 text-xs font-semibold tracking-wider uppercase">
                        {{ __('internship.identity') }}
                    </p>
                    <x-ts-input
                        :label="__('internship.name')"
                        wire:model="form.name"
                        :placeholder="__('internship.name_placeholder')"
                        icon="academic-cap"
                    />
                    <x-ts-select.native
                        :label="__('internship.academic_year')"
                        wire:model="form.academic_year_id"
                        :options="$this->academicYears"
                        icon="calendar-days"
                        class="mt-4"
                    />
                    <x-ts-textarea
                        :label="__('internship.description')"
                        wire:model="form.description"
                        :placeholder="__('internship.description_placeholder')"
                        rows="2"
                        class="mt-4"
                    />
                </div>

                <div class="bg-base-200/30 border-base-content/10 rounded-xl border p-5">
                    <p class="text-base-content/50 mb-4 text-xs font-semibold tracking-wider uppercase">
                        {{ __('internship.dates') }}
                    </p>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-mary-datepicker
                            :label="__('internship.start_date')"
                            wire:model="form.start_date"
                            icon="calendar"
                        />
                        <x-mary-datepicker
                            :label="__('internship.end_date')"
                            wire:model="form.end_date"
                            icon="calendar"
                        />
                    </div>
                </div>

                <div class="bg-base-200/30 border-base-content/10 rounded-xl border p-5">
                    <p class="text-base-content/50 mb-4 text-xs font-semibold tracking-wider uppercase">
                        {{ __('internship.registration') }}
                    </p>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-mary-datepicker
                            :label="__('internship.registration_start_date')"
                            wire:model="form.registration_start_date"
                            icon="clock"
                        />
                        <x-mary-datepicker
                            :label="__('internship.registration_end_date')"
                            wire:model="form.registration_end_date"
                            icon="clock"
                        />
                    </div>
                </div>

                <div class="bg-base-200/30 border-base-content/10 rounded-xl border p-5">
                    <p class="text-base-content/50 mb-4 text-xs font-semibold tracking-wider uppercase">
                        {{ __('internship.configuration') }}
                    </p>
                    <x-ts-select.native
                        :label="__('internship.status')"
                        wire:model="form.status"
                        :options="$this->statusOptions"
                        icon="flag"
                    />
                </div>
            </div>
            <x-slot:actions>
                <x-ts-button :text="__('internship.cancel')" @click="$wire.showModal = false" color="white" sm />
                <x-ts-button :text="__('internship.save')" color="primary" sm wire:click="save" loading="save" />
            </x-slot:actions>
        </x-ts-modal>
    </x-slot:modal>
    @include('program.internship.components.internship-guide')
</x-core::ui.record-manager>
