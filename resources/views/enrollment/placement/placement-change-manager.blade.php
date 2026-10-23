<x-ui::components.record-manager :title="__('placement_change.title')" :subtitle="__('placement_change.subtitle')">
    <x-slot:filters>
        <x-ts-select.native
            wire:model.live="filters.status"
            :options="ts_options(['pending' => __('placement_change.status_pending'), 'approved' => __('placement_change.status_approved'), 'rejected' => __('placement_change.status_rejected')], __('placement_change.status'))"
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
                @php
                    $statusConfig = match ($r->status->value) {
                        'pending' => ['color' => 'amber', 'icon' => 'clock'],
                        'approved' => ['color' => 'emerald', 'icon' => 'check-circle'],
                        'rejected' => ['color' => 'rose', 'icon' => 'x-circle'],
                        default => ['color' => 'slate', 'icon' => 'question-mark-circle'],
                    };
                @endphp
                <x-ts-badge
                    :text="$r->status->label()"
                    :color="$statusConfig['color']"
                    :icon="$statusConfig['icon']"
                    light
                    round
                    xs
                />
            @endinteract

            @interact('column_created_at', $r)
                <div class="text-xs">
                    <span class="text-base-content font-medium">{{ $r->created_at?->translatedFormat('d M Y') ?? '—' }}</span>
                    <span class="text-base-content/50 block text-[11px]">{{ $r->created_at?->format('H:i') }}</span>
                </div>
            @endinteract

            @interact('column_reason', $r)
                <div class="text-base-content/80 line-clamp-2 max-w-xs text-xs" title="{{ $r->reason }}">
                    {{ $r->reason }}
                </div>
            @endinteract

            @interact('column_action', $r)
                <x-ui::components.table-actions>
                    @if ($r->status->value === 'pending')
                        <x-ts-button.circle
                            :aria-label="__('common.actions.approve')"
                            icon="check"
                            color="green"
                            outline
                            xs
                            wire:click="approve('{{ $r->id }}')"
                            tooltip="{{ __('common.actions.approve') }}"
                        />
                        <x-ts-button.circle
                            :aria-label="__('common.actions.reject')"
                            icon="x-mark"
                            color="red"
                            outline
                            xs
                            wire:click="rejectConfirm('{{ $r->id }}')"
                            tooltip="{{ __('common.actions.reject') }}"
                        />
                    @else
                        <span class="text-base-content/30 text-xs">—</span>
                    @endif
                </x-ui::components.table-actions>
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
                        color="slate"
                        outline
                        sm
                    />
                    <x-ts-button :text="__('placement_change.reject')" color="red" sm type="submit" loading="reject" />
                </div>
            </form>
        </x-ts-modal>
    </x-slot:modal>
    @include('enrollment.placement.components.placement-change-guide')
</x-ui::components.record-manager>
