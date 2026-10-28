<x-ui::components.record-manager :title="__('placement_change.title')" :subtitle="__('placement_change.subtitle')">
    <x-slot:headerActions>
        <x-ts-button
            :text="__('placement_change.relocate_student')"
            icon="arrows-right-left"
            color="primary"
            sm
            wire:click="openRelocateModal"
        />
    </x-slot:headerActions>

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
                        <x-ui::components.table-action
                            action="custom"
                            icon="check"
                            color="green"
                            wire:click="approve('{{ $r->id }}')"
                            :label="__('common.actions.approve')"
                        />
                        <x-ui::components.table-action
                            action="custom"
                            icon="x-mark"
                            color="red"
                            wire:click="rejectConfirm('{{ $r->id }}')"
                            :label="__('common.actions.reject')"
                        />
                    @else
                        <span class="text-base-content/30 text-xs">—</span>
                    @endif
                </x-ui::components.table-actions>
            @endinteract
        </x-ts-table>
    </div>

    <x-slot:modal>
        <x-ts-modal wire="showRelocateModal" :title="__('placement_change.relocate_modal_title')" blur>
            <form wire:submit="relocate" class="space-y-4">
                <p class="text-base-content/60 text-xs">{{ __('placement_change.relocate_modal_subtitle') }}</p>

                <x-ts-select.styled
                    :label="__('placement_change.select_student')"
                    wire:model.live="form.registration_id"
                    :options="$this->activeRegistrations->map(fn ($r) => ['label' => ($r->student?->name ?? '—').' ('.($r->placement?->company?->name ?? '—').')', 'value' => $r->id])->values()->all()"
                    select="label:label|value:value"
                    :placeholder="__('placement_change.student_placeholder')"
                    searchable
                    required
                />

                @if ($this->selectedRegistration)
                    <div class="bg-base-200/50 space-y-1 rounded-xl p-3 text-xs">
                        <div class="text-base-content/60">{{ __('placement_change.current_placement') }}:</div>
                        <div class="text-base-content font-medium">
                            {{ $this->selectedRegistration->placement?->company?->name ?? '—' }} ({{ $this->selectedRegistration->placement?->name ?? '—' }})
                        </div>
                        <div class="text-base-content/50 text-[11px]">
                            {{ __('placement.batch') }}: {{ $this->selectedRegistration->internship?->name ?? '—' }}
                        </div>
                    </div>

                    <x-ts-select.styled
                        :label="__('placement_change.target_placement')"
                        wire:model="form.to_placement_id"
                        :options="$this->targetPlacements->map(fn ($p) => ['label' => ($p->company?->name ?? '—').' - '.$p->name.' ('.__('placement.stats.available').': '.$p->availableSlots().')', 'value' => $p->id])->values()->all()"
                        select="label:label|value:value"
                        :placeholder="__('placement_change.target_placeholder')"
                        searchable
                        required
                    />
                @endif

                <x-ts-textarea
                    :label="__('placement_change.offline_reason')"
                    wire:model="form.reason"
                    :placeholder="__('placement_change.offline_reason_placeholder')"
                    rows="3"
                    required
                />

                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('showRelocateModal', false)"
                        color="slate"
                        outline
                        sm
                    />
                    <x-ts-button
                        :text="__('placement_change.relocate_submit')"
                        color="primary"
                        sm
                        type="submit"
                        loading="relocate"
                    />
                </div>
            </form>
        </x-ts-modal>

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
