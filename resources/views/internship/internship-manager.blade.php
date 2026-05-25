<x-shared::ui.record-manager
    :title="__('internship.title')"
    :subtitle="__('internship.subtitle')"

>
    <x-slot:headerActions>
        <x-mary-button :label="__('internship.create_batch')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
    </x-slot:headerActions>

    <x-slot:extraMenu>
        <x-mary-menu-item :title="__('common.actions.import')" icon="o-arrow-up-tray" onclick="document.getElementById('import-csv').click()" />
        <input id="import-csv" type="file" accept=".csv" wire:model="importFile" class="hidden" />
        <x-mary-menu-item :title="__('common.actions.export')" icon="o-arrow-down-tray" wire:click="export" />
        <x-mary-menu-item :title="__('common.actions.template')" icon="o-document-arrow-down" wire:click="downloadTemplate" />
    </x-slot:extraMenu>

    <x-slot:stats>
        <x-shared::widgets.stat-card icon="o-calendar" :title="__('internship.stats.total')" :value="$this->stats['total']" />
        <x-shared::widgets.stat-card icon="o-play" :title="__('internship.stats.active')" :value="$this->stats['active']" />
        <x-shared::widgets.stat-card icon="o-briefcase" :title="__('internship.stats.total_placements')" :value="$this->stats['total_placements']" />
        <x-shared::widgets.stat-card icon="o-user-group" :title="__('internship.stats.total_registrations')" :value="$this->stats['total_registrations']" />
    </x-slot:stats>

    <x-slot:filters>
        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('internship.status') }}</label>
        <select wire:model.live="filters.status" class="select select-bordered select-sm w-full text-sm">
            <option value="">{{ __('internship.all_statuses') }}</option>
            <option value="active">{{ __('internship.statuses.active') }}</option>
            <option value="published">{{ __('internship.statuses.published') }}</option>
            <option value="completed">{{ __('internship.statuses.completed') }}</option>
            <option value="draft">{{ __('internship.statuses.draft') }}</option>
            <option value="cancelled">{{ __('internship.statuses.cancelled') }}</option>
        </select>

        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('internship.filter_academic_year') }}</label>
        <select wire:model.live="filters.academic_year_id" class="select select-bordered select-sm w-full text-sm">
            <option value="">{{ __('internship.select_academic_year') }}</option>
            @foreach($this->academicYears as $year)
                <option value="{{ $year['id'] }}">{{ $year['name'] }}</option>
            @endforeach
        </select>

        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('internship.filter_date_from') }}</label>
        <input wire:model.live="filters.date_from" type="date" class="input input-bordered input-sm w-full text-sm" />

        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('internship.filter_date_to') }}</label>
        <input wire:model.live="filters.date_to" type="date" class="input input-bordered input-sm w-full text-sm" />
    </x-slot:filters>

    <x-shared::ui.selection-bar>
        <x-mary-dropdown>
            <x-slot:trigger>
                <x-mary-button icon="o-chevron-down" class="btn-sm btn-primary font-medium" :label="__('common.actions.bulk_actions')" />
            </x-slot:trigger>
            <div class="p-1.5 w-48">
                <x-mary-menu-item :title="__('common.actions.export_selected')" icon="o-arrow-down-tray"
                    wire:click="exportSelected" />
                <hr class="border-base-content/10" />
                <x-mary-menu-item :title="__('common.actions.delete_selected')" icon="o-trash" class="text-error"
                    wire:click="askDeleteSelected" />
                <x-mary-menu-item :title="__('internship.complete_filtered')" icon="o-check-circle"
                    wire:click="askCloseFiltered" />
            </div>
        </x-mary-dropdown>
    </x-shared::ui.selection-bar>

    <div class="overflow-x-auto">
        <x-mary-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            selectable
            wire:model="selectedIds"
            class="table-sm"
        >
            @scope('cell_start_date', $internship)
                <span class="text-sm font-medium">{{ $internship->start_date->format('d M Y') }}</span>
            @endscope

            @scope('cell_end_date', $internship)
                <span class="text-sm font-medium">{{ $internship->end_date->format('d M Y') }}</span>
            @endscope

            @scope('cell_status', $internship)
                @php
                    $statusClass = match($internship->status->value) {
                        'active' => 'badge-success',
                        'published' => 'badge-info',
                        'completed' => 'badge-neutral',
                        'cancelled' => 'badge-error',
                        default => 'badge-ghost',
                    };
                @endphp
                <x-mary-badge :value="__('internship.statuses.' . $internship->status->value)" class="{{ $statusClass }} font-bold text-[10px] uppercase tracking-tighter" />
            @endscope

            @scope('actions', $internship)
                <div class="flex justify-end gap-1">
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="edit('{{ $internship->id }}')" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error"
                        wire:click="askDelete('{{ $internship->id }}')" />
                </div>
            @endscope
        </x-mary-table>
    </div>

    {{-- Confirm Dialog --}}
    <x-shared::ui.confirm
        wire:model="showConfirm"
        :message="$confirmMessage"
        confirmText="{{ __('common.actions.confirm') }}"
        cancelText="{{ __('common.actions.cancel') }}"
        :confirmClass="$confirmType === 'close_filtered' ? 'btn-primary' : 'btn-error'"
    />

    <x-slot:modal>
        <x-mary-modal wire:model="showModal" :title="$form->id ? __('internship.edit_batch') : __('internship.new_batch')" separator class="backdrop-blur-sm">
            <div class="space-y-6">
                <x-mary-input :label="__('internship.name')" wire:model="form.name" :placeholder="__('internship.name_placeholder')" icon="o-academic-cap" class="rounded-xl border-base-300" />
                <x-mary-select :label="__('internship.academic_year')" wire:model="form.academic_year_id" :options="$this->academicYears" icon="o-calendar-days" class="rounded-xl border-base-300" />
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-mary-datepicker :label="__('internship.start_date')" wire:model="form.start_date" icon="o-calendar" class="rounded-xl border-base-300" />
                    <x-mary-datepicker :label="__('internship.end_date')" wire:model="form.end_date" icon="o-calendar" class="rounded-xl border-base-300" />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-mary-datepicker :label="__('internship.registration_start_date')" wire:model="form.registration_start_date" icon="o-clock" class="rounded-xl border-base-300" />
                    <x-mary-datepicker :label="__('internship.registration_end_date')" wire:model="form.registration_end_date" icon="o-clock" class="rounded-xl border-base-300" />
                </div>
                <x-mary-select :label="__('internship.status')" wire:model="form.status" :options="$this->statusOptions" icon="o-flag" class="rounded-xl border-base-300" />
                <x-mary-textarea :label="__('internship.description')" wire:model="form.description" :placeholder="__('internship.description_placeholder')" rows="2" icon="o-document-text" class="rounded-xl border-base-300" />
            </div>
            <x-slot:actions>
                <x-mary-button :label="__('internship.cancel')" @click="$wire.showModal = false" class="rounded-xl" />
                <x-mary-button :label="__('internship.save')" class="btn-primary rounded-xl font-bold uppercase tracking-widest" wire:click="save" spinner="save" />
            </x-slot:actions>
        </x-mary-modal>
    </x-slot:modal>
</x-shared::ui.record-manager>
