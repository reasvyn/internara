<x-ui::record-manager
    :title="__('report.title')"
    :subtitle="__('report.subtitle')"
>
    <x-slot:filters>
        <x-mary-select wire:model.live="filters.status" :placeholder="__('report.status')"
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
            @scope('cell_status', $r)
                <x-mary-badge :value="$r->status->label()" :class="match($r->status->value) {
                    'draft' => 'badge-ghost', 'submitted' => 'badge-info', 'revision_required' => 'badge-warning', 'approved' => 'badge-success', default => 'badge-ghost',
                }" />
            @endscope

            @scope('cell_submitted_at', $r)
                <span class="text-sm">{{ $r->submitted_at?->format('d M Y') ?? '—' }}</span>
            @endscope

            @scope('cell_score', $r)
                <span class="text-sm">{{ $r->score ?? '—' }}</span>
            @endscope

            @scope('actions', $r)
                <div class="flex justify-end gap-1">
                    @if($r->status->value === 'submitted')
                        <x-mary-button icon="o-check-circle" class="btn-ghost btn-sm text-success"
                            wire:click="grade('{{ $r->id }}')" />
                        <x-mary-button icon="o-arrow-uturn-left" class="btn-ghost btn-sm text-warning"
                            wire:click="requestRevision('{{ $r->id }}')" />
                    @endif
                </div>
            @endscope
        </x-mary-table>
    </div>

    <x-slot:modal>
        <x-mary-modal wire:model="showGradeModal" :title="__('report.grade_title')" class="backdrop-blur-sm">
            <x-mary-form wire:submit="saveGrade">
                <div class="space-y-5">
                    <x-mary-input :label="__('report.score')" wire:model="gradeData.score" type="number" min="0" max="100" />
                    <x-mary-textarea :label="__('report.feedback')" wire:model="gradeData.feedback" rows="3" />
                </div>
                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showGradeModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('report.approve')" class="btn-primary btn-sm" type="submit" spinner="saveGrade" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>
</x-ui::record-manager>
