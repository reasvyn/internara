<x-shared::ui.record-manager
    :title="__('placement_change.title')"
    :subtitle="__('placement_change.subtitle')"
>
    <x-slot:filters>
        <x-mary-select wire:model.live="filters.status" :placeholder="__('placement_change.status')"
            :options="['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']" />
    </x-slot:filters>

    <div class="overflow-x-auto">
        <x-mary-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            class="table-sm"
        >
            @scope('cell_status', $r)
                <x-mary-badge :value="$r->status->label()" :class="match($r->status->value) {
                    'pending' => 'badge-warning', 'approved' => 'badge-success', 'rejected' => 'badge-error', default => 'badge-ghost',
                }" />
            @endscope

            @scope('cell_created_at', $r)
                <span class="text-sm">{{ $r->created_at?->format('d M Y H:i') ?? '—' }}</span>
            @endscope

            @scope('actions', $r)
                <div class="flex justify-end gap-1">
                    @if($r->status->value === 'pending')
                        <x-mary-button icon="o-check" class="btn-ghost btn-sm text-success"
                            wire:click="approve('{{ $r->id }}')" />
                        <x-mary-button icon="o-x-mark" class="btn-ghost btn-sm text-error"
                            wire:click="rejectConfirm('{{ $r->id }}')" />
                    @endif
                </div>
            @endscope
        </x-mary-table>
    </div>

    <x-slot:modal>
        <x-mary-modal wire:model="showRejectModal" :title="__('placement_change.reject_title')" class="backdrop-blur-sm">
            <x-mary-form wire:submit="reject">
                <x-mary-textarea :label="__('placement_change.rejection_reason')" wire:model="rejectionReason" rows="3" />
                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showRejectModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('placement_change.reject')" class="btn-error btn-sm" type="submit" spinner="reject" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>
</x-shared::ui.record-manager>
