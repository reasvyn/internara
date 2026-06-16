<x-core::ui.record-manager
    :title="__('guidance.review_title')"
    :subtitle="__('guidance.review_subtitle')"
>
    <x-mary-table
        :headers="$this->headers()"
        :rows="$this->rows()"
        :sort-by="$sortBy"
        with-pagination
        class="table-sm"
    >
        @scope('cell_status', $l)
            <x-mary-badge :value="$l->status->label()" :class="match($l->status->value) {
                'submitted' => 'badge-info',
                'reviewed' => 'badge-success',
                default => 'badge-ghost',
            }" />
        @endscope

        @scope('actions', $l)
            <div class="flex justify-end gap-1">
                @if($l->status->value === 'submitted')
                    <x-mary-button :label="__('guidance.review')" icon="o-check" class="btn-ghost btn-sm text-success" wire:click="askReview('{{ $l->id }}')" />
                @endif
            </div>
        @endscope
    </x-mary-table>

    <x-slot:modal>
        <x-mary-modal wire:model="showReviewModal" :title="__('guidance.review_log')" separator class="backdrop-blur-sm">
            <x-mary-form wire:submit="confirmReview" class="space-y-5">
                <x-mary-textarea :label="__('guidance.feedback')" wire:model="feedback" rows="4" />

                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="$set('showReviewModal', false)" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('guidance.submit_review')" class="btn-primary btn-sm" type="submit" spinner="confirmReview" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    </x-slot:modal>
</x-core::ui.record-manager>
