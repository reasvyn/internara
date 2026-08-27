<x-ui::components.record-manager :title="__('certificate.issued_title')" :subtitle="__('certificate.issued_subtitle')">
    <x-slot:headerActions>
        <x-ts-button :text="__('certificate.issue')" icon="document-check" color="green" sm wire:click="issue" />
        <x-ts-button
            :text="__('certificate.batch_issue')"
            icon="rocket-launch"
            color="secondary"
            sm
            wire:click="batchIssue"
        />
    </x-slot:headerActions>

    <x-slot:filters>
        <x-ts-select.native
            wire:model.live="filters.status"
            :options="[null => __('certificate.filter_status')] + (collect($statusOptions)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->toArray())"
        />
    </x-slot:filters>

    <div class="overflow-x-auto">
        <x-ts-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            class="table-sm"
        >
            @interact('column_status', $c)
                <x-ts-badge
                    :text="$c->status->label()"
                    :class="$c->status->value === 'issued' ? 'badge-success' : 'badge-error'"
                />
            @endinteract

            @interact('column_issued_at', $c)
                <span class="text-sm">{{ $c->issued_at?->format('d M Y') ?? '—' }}</span>
            @endinteract

            @interact('column_action', $c)
                <div class="flex justify-end gap-1">
                    @if ($c->status->value === 'issued')
                        <x-ts-button
                            icon="x-circle"
                            class="text-error"
                            color="white"
                            sm
                            wire:click="askRevoke('{{ $c->id }}')"
                            :aria-label="__('certificate.revoke')"
                        />
                    @endif
                </div>
            @endinteract
        </x-ts-table>
    </div>

    <x-slot:modal>
        <x-ts-modal wire="showIssueModal" :title="__('certificate.issue_title')" blur>
            <form wire:submit="saveIssue">
                <div class="space-y-5">
                    <x-ts-select.native
                        :label="__('certificate.registration')"
                        wire:model="issueRegistrationId"
                        :options="[null => __('certificate.registration_placeholder')] + ($this->activeRegistrations)"
                        option-label="name"
                        option-value="id"
                    />
                    <x-ts-select.native
                        :label="__('certificate.template')"
                        wire:model="issueTemplateId"
                        :options="[null => __('certificate.template_placeholder')] + ($this->templates)"
                        option-label="name"
                        option-value="id"
                    />
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('showIssueModal', false)"
                        color="white"
                        sm
                    />
                    <x-ts-button :text="__('certificate.issue')" color="green" sm type="submit" loading="saveIssue" />
                </div>
            </form>
        </x-ts-modal>

        <x-ts-modal wire="showBatchIssueModal" :title="__('certificate.batch_issue_title')" blur>
            <form wire:submit="saveBatchIssue">
                <div class="space-y-5">
                    <x-ts-select.native
                        :label="__('certificate.template')"
                        wire:model="batchIssueTemplateId"
                        :options="[null => __('certificate.template_placeholder')] + ($this->templates)"
                        option-label="name"
                        option-value="id"
                    />
                    <x-ts-select.native
                        :label="__('certificate.batch_filter_status')"
                        wire:model="batchIssueFilter"
                        :options="['active' => __('certificate.filter_active_registrations'), 'completed' => __('certificate.filter_completed_registrations')]"
                    />
                    <div class="bg-base-200 rounded-lg p-3 text-sm">
                        <p class="text-base-content/70">{{ __('certificate.batch_issue_info') }}</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('showBatchIssueModal', false)"
                        color="white"
                        sm
                    />
                    <x-ts-button
                        :text="__('certificate.batch_issue')"
                        color="secondary"
                        sm
                        type="submit"
                        loading="saveBatchIssue"
                    />
                </div>
            </form>
        </x-ts-modal>
    </x-slot:modal>
    @include('certification.certificate.components.certificate-guide')
</x-ui::components.record-manager>
