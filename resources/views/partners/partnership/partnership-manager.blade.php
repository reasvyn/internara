<x-core::ui.record-manager
    :title="__('partnership.title')"
    :subtitle="__('partnership.subtitle')"
>
    <x-slot:headerActions>
        <x-mary-button :label="__('partnership.add')" icon="o-plus" class="btn-primary btn-sm" wire:click="create" />
    </x-slot:headerActions>

    <x-slot:extraMenu>
        <x-mary-menu-item :title="__('common.actions.import')" icon="o-arrow-up-tray" onclick="document.getElementById('import-csv').click()" />
        <input id="import-csv" type="file" accept=".csv" wire:model="importFile" class="hidden" />
        <x-mary-menu-item :title="__('common.actions.export')" icon="o-arrow-down-tray" wire:click="export" />
        <x-mary-menu-item :title="__('common.actions.template')" icon="o-document-arrow-down" wire:click="downloadTemplate" />
    </x-slot:extraMenu>

    <x-slot:stats>
        <x-core::widgets.stat-card icon="o-hand-raised" :title="__('partnership.stats_active')" :value="$this->stats['active']" />
        <x-core::widgets.stat-card icon="o-clock" :title="__('partnership.stats_expiring_soon', ['days' => 30])" :value="$this->stats['expiring_soon']" />
        <x-core::widgets.stat-card icon="o-exclamation-circle" :title="__('partnership.stats_expired')" :value="$this->stats['expired']" />
        <x-core::widgets.stat-card icon="o-document-text" :title="__('partnership.stats_total')" :value="$this->stats['total']" />
    </x-slot:stats>

    <x-slot:filters>
        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('partnership.status') }}</label>
        <select wire:model.live="filters.status" class="select select-bordered select-sm w-full text-sm">
            <option value="">{{ __('common.actions.all') }}</option>
            @foreach($this->statusOptions as $opt)
                <option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>
            @endforeach
        </select>

        <label class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ __('partnership.company') }}</label>
        <select wire:model.live="filters.company_id" class="select select-bordered select-sm w-full text-sm">
            <option value="">{{ __('common.actions.all') }}</option>
            @foreach($this->companies as $company)
                <option value="{{ $company['id'] }}">{{ $company['name'] }}</option>
            @endforeach
        </select>
    </x-slot:filters>

    <x-core::ui.selection-bar>
        <x-mary-dropdown>
            <x-slot:trigger>
                <x-mary-button icon="o-chevron-down" class="btn-sm btn-primary font-medium" :label="__('common.actions.bulk_actions')" />
            </x-slot:trigger>
            <div class="p-1.5 w-48">
                <x-mary-menu-item :title="__('common.actions.export_selected')" icon="o-arrow-down-tray" wire:click="exportSelected" />
                <hr class="border-base-content/10" />
                <x-mary-menu-item :title="__('common.actions.delete_selected')" icon="o-trash" class="text-error" wire:click="askDeleteSelected" />
            </div>
        </x-mary-dropdown>
    </x-core::ui.selection-bar>

    <x-core::ui.confirm
        wire:model="showConfirm"
        :message="$confirmMessage"
        confirmText="{{ __('common.actions.confirm') }}"
        cancelText="{{ __('common.actions.cancel') }}"
        :confirmClass="$confirmType === 'terminate' ? 'btn-warning' : 'btn-error'"
    />

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
            @scope('cell_company_name', $p)
                <span class="font-medium text-sm">{{ $p->company_name }}</span>
            @endscope

            @scope('cell_status', $p)
                <x-mary-badge :value="$p->status->label()" :class="match($p->status->value) {
                    'active' => 'badge-success',
                    'expired' => 'badge-warning',
                    'terminated' => 'badge-error',
                    default => 'badge-ghost',
                }" />
            @endscope

            @scope('cell_start_date', $p)
                <span class="text-sm">{{ $p->start_date?->format('d M Y') ?? '—' }}</span>
            @endscope

            @scope('cell_end_date', $p)
                <span class="text-sm">{{ $p->end_date?->format('d M Y') ?? '—' }}</span>
            @endscope

            @scope('actions', $p)
                <div class="flex justify-end gap-1">
                    @if($p->status->value === 'active')
                        <x-mary-button icon="o-x-circle" class="btn-ghost btn-sm text-warning"
                            wire:click="askTerminate('{{ $p->id }}')"
                            :aria-label="__('partnership.terminate')" />
                    @endif
                    <x-mary-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="edit('{{ $p->id }}')" :aria-label="__('common.actions.edit')" />
                    <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error"
                        wire:click="askDelete('{{ $p->id }}')"
                        :aria-label="__('common.actions.delete')" />
                </div>
            @endscope
        </x-mary-table>
    </div>

    <x-slot:modal>
        <x-mary-modal wire:model="showModal" :title="$form->id ? __('partnership.edit') : __('partnership.new')" separator class="backdrop-blur-sm">
            <x-mary-form wire:submit="save" class="space-y-5">
                <div class="bg-base-200/30 border border-base-content/10 rounded-xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-4">{{ __('partnership.identity') }}</p>
                    <x-mary-select
                        :label="__('partnership.company')"
                        wire:model="form.company_id"
                        :options="$this->companies"
                        option-label="name"
                        option-value="id"
                        :placeholder="__('partnership.company_placeholder')"
                        icon="o-building-office"
                    />
                    <x-mary-input :label="__('partnership.agreement_number')" wire:model="form.agreement_number" :placeholder="__('partnership.agreement_number_placeholder')" icon="o-document-text" />
                    <x-mary-input :label="__('partnership.title_field')" wire:model="form.title" :placeholder="__('partnership.title_placeholder')" icon="o-briefcase" />
                </div>

                <div class="bg-base-200/30 border border-base-content/10 rounded-xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-4">{{ __('partnership.period') }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-mary-input :label="__('partnership.start_date')" wire:model="form.start_date" type="date" icon="o-calendar" />
                        <x-mary-input :label="__('partnership.end_date')" wire:model="form.end_date" type="date" icon="o-calendar" />
                    </div>
                </div>

                <div class="bg-base-200/30 border border-base-content/10 rounded-xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-4">{{ __('partnership.contact') }}</p>
                    <x-mary-textarea :label="__('partnership.scope')" wire:model="form.scope" :placeholder="__('partnership.scope_placeholder')" rows="2" icon="o-rectangle-stack" />
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <x-mary-input :label="__('partnership.contact_person_name')" wire:model="form.contact_person_name" :placeholder="__('partnership.contact_person_name_placeholder')" icon="o-user" />
                        <x-mary-input :label="__('partnership.contact_person_phone')" wire:model="form.contact_person_phone" :placeholder="__('partnership.contact_person_phone_placeholder')" icon="o-phone" />
                        <x-mary-input :label="__('partnership.contact_person_email')" wire:model="form.contact_person_email" :placeholder="__('partnership.contact_person_email_placeholder')" icon="o-envelope" />
                    </div>
                </div>

                <div class="bg-base-200/30 border border-base-content/10 rounded-xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-4">{{ __('partnership.signing') }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-mary-input :label="__('partnership.signed_by_school')" wire:model="form.signed_by_school" :placeholder="__('partnership.signed_by_school_placeholder')" icon="o-academic-cap" />
                        <x-mary-input :label="__('partnership.signed_by_company')" wire:model="form.signed_by_company" :placeholder="__('partnership.signed_by_company_placeholder')" icon="o-building-office" />
                        <x-mary-input :label="__('partnership.signed_at')" wire:model="form.signed_at" type="date" icon="o-calendar-days" />
                    </div>
                </div>

                <div class="bg-base-200/30 border border-base-content/10 rounded-xl p-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-4">{{ __('partnership.documents') }}</p>
                    <x-mary-textarea :label="__('partnership.notes')" wire:model="form.notes" :placeholder="__('partnership.notes_placeholder')" rows="2" icon="o-document-text" />
                    <x-mary-file :label="__('partnership.mou_document')" wire:model="mouDocument" accept="pdf,jpg,jpeg,png" />
                </div>

                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('partnership.save')" class="btn-primary btn-sm" type="submit" spinner="save" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>
</x-core::ui.record-manager>
