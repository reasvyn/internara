<x-core::ui.record-manager
    :title="__('certificate.issued_title')"
    :subtitle="__('certificate.issued_subtitle')"
>
    <x-slot:headerActions>
        <x-mary-button :label="__('certificate.issue')" icon="o-document-check" class="btn-success btn-sm" wire:click="issue" />
        <x-mary-button :label="__('certificate.batch_issue')" icon="o-rocket-launch" class="btn-secondary btn-sm" wire:click="batchIssue" />
    </x-slot:headerActions>

    <x-slot:filters>
        <x-mary-select wire:model.live="filters.status" :placeholder="__('certificate.status')"
            :options="collect($statusOptions)->mapWithKeys(fn($s) => [$s->value => $s->label()])->toArray()" />
    </x-slot:filters>

    <div class="overflow-x-auto">
        <x-mary-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            class="table-sm"
        >
            @scope('cell_status', $c)
                <x-mary-badge :value="$c->status->label()" :class="$c->status->value === 'issued' ? 'badge-success' : 'badge-error'" />
            @endscope

            @scope('cell_issued_at', $c)
                <span class="text-sm">{{ $c->issued_at?->format('d M Y') ?? '—' }}</span>
            @endscope

            @scope('actions', $c)
                <div class="flex justify-end gap-1">
                    @if($c->status->value === 'issued')
                        <x-mary-button icon="o-x-circle" class="btn-ghost btn-sm text-error"
                            wire:click="askRevoke('{{ $c->id }}')"
                            :aria-label="__('certificate.revoke')" />
                    @endif
                </div>
            @endscope
        </x-mary-table>
    </div>

    <x-core::ui.confirm :message="__('certificate.revoke_confirm')" />

    <x-slot:modal>
        <x-mary-modal wire:model="showIssueModal" :title="__('certificate.issue_title')" class="backdrop-blur-sm">
            <x-mary-form wire:submit="saveIssue">
                <div class="space-y-5">
                    <x-mary-select :label="__('certificate.registration')" wire:model="issueRegistrationId"
                        :placeholder="__('certificate.registration_placeholder')"
                        :options="$this->activeRegistrations" option-label="name" option-value="id" />
                    <x-mary-select :label="__('certificate.template')" wire:model="issueTemplateId"
                        :placeholder="__('certificate.template_placeholder')"
                        :options="$this->templates" option-label="name" option-value="id" />
                </div>
                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showIssueModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('certificate.issue')" class="btn-success btn-sm" type="submit" spinner="saveIssue" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>

        <x-mary-modal wire:model="showBatchIssueModal" :title="__('certificate.batch_issue_title')" class="backdrop-blur-sm">
            <x-mary-form wire:submit="saveBatchIssue">
                <div class="space-y-5">
                    <x-mary-select :label="__('certificate.template')" wire:model="batchIssueTemplateId"
                        :placeholder="__('certificate.template_placeholder')"
                        :options="$this->templates" option-label="name" option-value="id" />
                    <x-mary-select :label="__('certificate.filter_status')" wire:model="batchIssueFilter"
                        :options="['active' => 'Active Registrations', 'completed' => 'Completed Registrations']" />
                    <div class="p-3 bg-base-200 rounded-lg text-sm">
                        <p class="text-base-content/70">{{ __('certificate.batch_issue_info') }}</p>
                    </div>
                    @if($batchResults)
                        <div class="p-3 bg-success/10 rounded-lg text-sm">
                            <p>{{ __('certificate.batch_results', ['success' => $batchResults['success'], 'failed' => $batchResults['failed']]) }}</p>
                        </div>
                    @endif
                </div>
                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showBatchIssueModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('certificate.batch_issue')" class="btn-secondary btn-sm" type="submit" spinner="saveBatchIssue" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>
</x-core::ui.record-manager>
