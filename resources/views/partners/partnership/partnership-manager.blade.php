<x-ui::components.record-manager :title="__('partnership.title')" :subtitle="__('partnership.subtitle')">
    <x-slot:headerActions>
        <x-ts-button :text="__('partnership.add')" icon="plus" color="primary" sm wire:click="create" />
    </x-slot:headerActions>

    <x-slot:extraMenu>
        <x-ts-dropdown.items :text="__('common.actions.import')" x-on:click="$refs.importCsvInput.click()" />
        <input x-ref="importCsvInput" type="file" accept=".csv" wire:model="importFile" class="hidden" />
        <x-ts-dropdown.items :text="__('common.actions.export')" icon="arrow-down-tray" wire:click="export" />
        <x-ts-dropdown.items
            :text="__('common.actions.template')"
            icon="document-arrow-down"
            wire:click="downloadTemplate"
        />
    </x-slot:extraMenu>

    <x-slot:stats>
        <x-ui::widgets.stat-card
            icon="hand-raised"
            :title="__('partnership.stats_active')"
            :value="$this->stats['active']"
        />
        <x-ui::widgets.stat-card
            icon="clock"
            :title="__('partnership.stats_expiring_soon', ['days' => 30])"
            :value="$this->stats['expiring_soon']"
        />
        <x-ui::widgets.stat-card
            icon="exclamation-circle"
            :title="__('partnership.stats_expired')"
            :value="$this->stats['expired']"
        />
        <x-ui::widgets.stat-card
            icon="document-text"
            :title="__('partnership.stats_total')"
            :value="$this->stats['total']"
        />
    </x-slot:stats>

    <x-slot:filters>
        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('partnership.status') }}</label>
        <x-ts-select.native wire:model.live="filters.status" class="w-full text-sm">
            <option value="">{{ __('common.actions.all') }}</option>
            @foreach ($this->statusOptions as $opt)
                <option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>
            @endforeach
        </x-ts-select.native>

        <label class="text-base-content/50 text-xs font-semibold tracking-wider uppercase">{{ __('partnership.company') }}</label>
        <x-ts-select.native wire:model.live="filters.company_id" class="w-full text-sm">
            <option value="">{{ __('common.actions.all') }}</option>
            @foreach ($this->companies as $company)
                <option value="{{ $company['id'] }}">{{ $company['name'] }}</option>
            @endforeach
        </x-ts-select.native>
    </x-slot:filters>

    <x-ui::components.selection-bar>
        <x-ts-dropdown>
            <x-slot:action>
                <x-ts-button
                    icon="chevron-down"
                    class="font-medium"
                    color="primary"
                    sm
                    :text="__('common.actions.bulk_actions')"
                / x-on:click="show = ! show">
            </x-slot:action>
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
            </div>
        </x-ts-dropdown>
    </x-ui::components.selection-bar>

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
            @interact('column_company_name', $p)
                <span class="text-sm font-medium">{{ $p->company_name }}</span>
            @endinteract

            @interact('column_status', $p)
                <x-ts-badge
                    :text="$p->status->label()"
                    :class="match($p->status->value) {
                    'active' => 'badge-success',
                    'expired' => 'badge-warning',
                    'terminated' => 'badge-error',
                    default => 'badge-ghost',
                }"
                />
            @endinteract

            @interact('column_start_date', $p)
                <span class="text-sm">{{ $p->start_date?->format('d M Y') ?? '—' }}</span>
            @endinteract

            @interact('column_end_date', $p)
                <span class="text-sm">{{ $p->end_date?->format('d M Y') ?? '—' }}</span>
            @endinteract

            @interact('column_action', $p)
                <div class="flex justify-end gap-1">
                    @if ($p->status->value === 'active')
                        <x-ts-button
                            icon="x-circle"
                            class="text-warning"
                            color="white"
                            sm
                            wire:click="askTerminate('{{ $p->id }}')"
                            :aria-label="__('partnership.terminate')"
                        />
                    @endif
                    <x-ts-button
                        icon="pencil"
                        color="white"
                        sm
                        wire:click="edit('{{ $p->id }}')"
                        :aria-label="__('common.actions.edit')"
                    />
                    <x-ts-button
                        icon="trash"
                        class="text-error"
                        color="white"
                        sm
                        wire:click="askDelete('{{ $p->id }}')"
                        :aria-label="__('common.actions.delete')"
                    />
                </div>
            @endinteract
        </x-ts-table>
    </div>

    <x-slot:modal>
        <x-ts-modal wire="showModal" :title="$form->id ? __('partnership.edit') : __('partnership.new')" separator blur>
            <form wire:submit="save" class="space-y-5">
                <div class="bg-base-200/30 border-base-content/10 rounded-xl border p-5">
                    <p class="text-base-content/50 mb-4 text-xs font-semibold tracking-wider uppercase">
                        {{ __('partnership.identity') }}
                    </p>
                    <x-ts-select.native
                        :label="__('partnership.company')"
                        wire:model="form.company_id"
                        :options="[null => __('partnership.company_placeholder')] + ($this->companies)"
                        option-label="name"
                        option-value="id"
                        icon="building-office"
                    />
                    <x-ts-input
                        :label="__('partnership.agreement_number')"
                        wire:model="form.agreement_number"
                        :placeholder="__('partnership.agreement_number_placeholder')"
                        icon="document-text"
                    />
                    <x-ts-input
                        :label="__('partnership.title_field')"
                        wire:model="form.title"
                        :placeholder="__('partnership.title_placeholder')"
                        icon="briefcase"
                    />
                </div>

                <div class="bg-base-200/30 border-base-content/10 rounded-xl border p-5">
                    <p class="text-base-content/50 mb-4 text-xs font-semibold tracking-wider uppercase">
                        {{ __('partnership.period') }}
                    </p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ts-input
                            :label="__('partnership.start_date')"
                            wire:model="form.start_date"
                            type="date"
                            icon="calendar"
                        />
                        <x-ts-input
                            :label="__('partnership.end_date')"
                            wire:model="form.end_date"
                            type="date"
                            icon="calendar"
                        />
                    </div>
                </div>

                <div class="bg-base-200/30 border-base-content/10 rounded-xl border p-5">
                    <p class="text-base-content/50 mb-4 text-xs font-semibold tracking-wider uppercase">
                        {{ __('partnership.contact') }}
                    </p>
                    <x-ts-textarea
                        :label="__('partnership.scope')"
                        wire:model="form.scope"
                        :placeholder="__('partnership.scope_placeholder')"
                        rows="2"
                    />
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <x-ts-input
                            :label="__('partnership.contact_person_name')"
                            wire:model="form.contact_person_name"
                            :placeholder="__('partnership.contact_person_name_placeholder')"
                            icon="user"
                        />
                        <x-ts-input
                            :label="__('partnership.contact_person_phone')"
                            wire:model="form.contact_person_phone"
                            :placeholder="__('partnership.contact_person_phone_placeholder')"
                            icon="phone"
                        />
                        <x-ts-input
                            :label="__('partnership.contact_person_email')"
                            wire:model="form.contact_person_email"
                            :placeholder="__('partnership.contact_person_email_placeholder')"
                            icon="envelope"
                        />
                    </div>
                </div>

                <div class="bg-base-200/30 border-base-content/10 rounded-xl border p-5">
                    <p class="text-base-content/50 mb-4 text-xs font-semibold tracking-wider uppercase">
                        {{ __('partnership.signing') }}
                    </p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ts-input
                            :label="__('partnership.signed_by_school')"
                            wire:model="form.signed_by_school"
                            :placeholder="__('partnership.signed_by_school_placeholder')"
                            icon="academic-cap"
                        />
                        <x-ts-input
                            :label="__('partnership.signed_by_company')"
                            wire:model="form.signed_by_company"
                            :placeholder="__('partnership.signed_by_company_placeholder')"
                            icon="building-office"
                        />
                        <x-ts-input
                            :label="__('partnership.signed_at')"
                            wire:model="form.signed_at"
                            type="date"
                            icon="calendar-days"
                        />
                    </div>
                </div>

                <div class="bg-base-200/30 border-base-content/10 rounded-xl border p-5">
                    <p class="text-base-content/50 mb-4 text-xs font-semibold tracking-wider uppercase">
                        {{ __('partnership.documents') }}
                    </p>
                    <x-ts-textarea
                        :label="__('partnership.notes')"
                        wire:model="form.notes"
                        :placeholder="__('partnership.notes_placeholder')"
                        rows="2"
                    />
                    <x-ts-upload :label="__('partnership.mou_document')" wire:model="mouDocument" />
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('showModal', false)"
                        color="white"
                        sm
                    />
                    <x-ts-button :text="__('partnership.save')" color="primary" sm type="submit" loading="save" />
                </div>
            </form>
        </x-ts-modal>
    </x-slot:modal>
    @include('partners.partnership.components.partnership-guide')
</x-ui::components.record-manager>
