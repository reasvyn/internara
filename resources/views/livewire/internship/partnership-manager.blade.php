<x-ui::record-manager
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
        <x-widget::stat icon="o-hand-raised" :label="__('partnership.stats_active')" :value="$this->stats['active']" />
        <x-widget::stat icon="o-exclamation-triangle" :label="__('partnership.stats_expiring_soon', ['days' => 30])" :value="$this->stats['expiring_soon']" class="text-warning" />
        <x-widget::stat icon="o-clock" :label="__('partnership.stats_expired')" :value="$this->stats['expired']" />
    </x-slot:stats>

    <x-slot:filters>
        <x-mary-select
            wire:model.live="filters.status"
            :placeholder="__('partnership.status')"
            :options="$this->statusOptions"
            option-label="name"
            option-value="id"
            clearable
        />
        <x-mary-select
            wire:model.live="filters.company_id"
            :placeholder="__('partnership.company')"
            :options="$this->companies"
            option-label="name"
            option-value="id"
            clearable
        />
    </x-slot:filters>

    <x-ui::selection-bar>
        <x-mary-dropdown>
            <x-slot:trigger>
                <x-mary-button icon="o-chevron-down" class="btn-sm btn-primary font-medium" :label="__('common.actions.bulk_actions')" />
            </x-slot:trigger>
            <div class="p-1.5 w-48">
                <x-mary-menu-item title="Delete Selected" icon="o-trash" class="text-error"
                    wire:click="askDeleteSelected" />
            </div>
        </x-mary-dropdown>
    </x-ui::selection-bar>

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

    {{-- Confirm Dialog --}}
    <x-ui::confirm
        wire:model="showConfirm"
        :message="$confirmMessage"
        confirmText="{{ __('common.actions.confirm') }}"
        cancelText="{{ __('common.actions.cancel') }}"
        :confirmClass="$confirmType === 'terminate' ? 'btn-warning' : 'btn-error'"
    />

    <x-slot:modal>
        <x-mary-modal wire:model="showModal" :title="$formData['id'] ? __('partnership.edit') : __('partnership.new')" class="backdrop-blur-sm">
            <x-mary-form wire:submit="save">
                <div class="space-y-5">
                    <x-mary-select
                        :label="__('partnership.company')"
                        wire:model="formData.company_id"
                        :options="$this->companies"
                        option-label="name"
                        option-value="id"
                        :placeholder="__('partnership.company_placeholder')"
                    />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-mary-input :label="__('partnership.agreement_number')" wire:model="formData.agreement_number" :placeholder="__('partnership.agreement_number_placeholder')" />
                        <x-mary-input :label="__('partnership.title_field')" wire:model="formData.title" :placeholder="__('partnership.title_placeholder')" />
                        <x-mary-input :label="__('partnership.start_date')" wire:model="formData.start_date" type="date" />
                        <x-mary-input :label="__('partnership.end_date')" wire:model="formData.end_date" type="date" />
                    </div>
                    <x-mary-textarea :label="__('partnership.scope')" wire:model="formData.scope" :placeholder="__('partnership.scope_placeholder')" rows="2" />
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-mary-input :label="__('partnership.contact_person_name')" wire:model="formData.contact_person_name" :placeholder="__('partnership.contact_person_name_placeholder')" />
                        <x-mary-input :label="__('partnership.contact_person_phone')" wire:model="formData.contact_person_phone" :placeholder="__('partnership.contact_person_phone_placeholder')" />
                        <x-mary-input :label="__('partnership.contact_person_email')" wire:model="formData.contact_person_email" :placeholder="__('partnership.contact_person_email_placeholder')" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <x-mary-input :label="__('partnership.signed_by_school')" wire:model="formData.signed_by_school" :placeholder="__('partnership.signed_by_school_placeholder')" />
                        <x-mary-input :label="__('partnership.signed_by_company')" wire:model="formData.signed_by_company" :placeholder="__('partnership.signed_by_company_placeholder')" />
                        <x-mary-input :label="__('partnership.signed_at')" wire:model="formData.signed_at" type="date" />
                    </div>
                    <x-mary-textarea :label="__('partnership.notes')" wire:model="formData.notes" :placeholder="__('partnership.notes_placeholder')" rows="2" />
                    <x-mary-file :label="__('partnership.mou_document')" wire:model="mouDocument" accept="pdf,jpg,jpeg,png" />
                </div>
                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('partnership.save')" class="btn-primary btn-sm" type="submit" spinner="save" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>
</x-ui::record-manager>
