<x-ui::ui.record-manager :title="__('placement_change.title')" :subtitle="__('placement_change.subtitle')">
    <x-slot:filters>
        <x-ts-select.native
            wire:model.live="filters.status"
            :options="[null => __('placement_change.status')] + (['pending' => __('placement_change.status_pending'), 'approved' => __('placement_change.status_approved'), 'rejected' => __('placement_change.status_rejected')])"
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
            @interact('column_status', $r)
                <x-ts-badge
                    :text="$r->status->label()"
                    :class="match($r->status->value) {
                    'pending' => 'badge-warning', 'approved' => 'badge-success', 'rejected' => 'badge-error', default => 'badge-ghost',
                }"
                />
            @endinteract

            @interact('column_created_at', $r)
                <span class="text-sm">{{ $r->created_at?->format('d M Y H:i') ?? '—' }}</span>
            @endinteract

            @interact('column_action', $r)
                <div class="flex justify-end gap-1">
                    @if ($r->status->value === 'pending')
                        <x-ts-button
                            aria-label="{{ __('common.actions.approve') }}"
                            icon="check"
                            class="text-success"
                            color="white"
                            sm
                            wire:click="approve('{{ $r->id }}')"
                        />
                        <x-ts-button
                            aria-label="{{ __('common.actions.reject') }}"
                            icon="x-mark"
                            class="text-error"
                            color="white"
                            sm
                            wire:click="rejectConfirm('{{ $r->id }}')"
                        />
                    @endif
                </div>
            @endinteract
        </x-ts-table>
    </div>

    <x-slot:modal>
        <x-ts-modal wire="showRejectModal" :title="__('placement_change.reject_title')" blur>
            <form wire:submit="reject">
                <x-ts-textarea :label="__('placement_change.rejection_reason')" wire:model="rejectionReason" rows="3" />
                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('showRejectModal', false)"
                        color="white"
                        sm
                    />
                    <x-ts-button :text="__('placement_change.reject')" color="red" sm type="submit" loading="reject" />
                </div>
            </form>
        </x-ts-modal>
    </x-slot:modal>
    @include('enrollment.placement.components.placement-change-guide')
</x-ui::ui.record-manager>
