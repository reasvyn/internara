<x-ui::components.record-manager :title="__('journals.review_title')" :subtitle="__('journals.review_subtitle')">
    <x-ts-table :headers="$this->headers()" :rows="$this->rows()" :sort-by="$sortBy" with-pagination class="table-sm">
        @interact('column_date', $l)
            <span class="text-sm">{{ $l->date?->format('d M Y') }}</span>
        @endinteract

        @interact('column_status', $l)
            <x-ts-badge
                :text="$l->status->label()"
                :color="match ($l->status->value) {
                    'submitted' => 'blue',
                    'reviewed', 'verified' => 'green',
                    'acknowledged' => 'primary',
                    default => 'gray',
                }"
            />
        @endinteract

        @interact('column_action', $l)
            <div class="flex justify-end gap-1">
                @if ($l->status->value === 'submitted')
                    <x-ts-button
                        :text="__('journals.review')"
                        icon="check"
                        class="text-success"
                        color="slate"
                        outline
                        sm
                        wire:click="askReview('{{ $l->id }}')"
                    />
                @endif
            </div>
        @endinteract
    </x-ts-table>

    <x-slot:modal>
        <x-ts-modal wire="showReviewModal" :title="__('journals.review_log')" separator blur>
            <form wire:submit="confirmReview" class="space-y-5">
                <x-ts-textarea :label="__('journals.feedback')" wire:model="feedback" rows="4" />

                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('showReviewModal', false)"
                        color="slate"
                        outline
                        sm
                    />
                    <x-ts-button
                        :text="__('journals.submit_review')"
                        color="primary"
                        sm
                        type="submit"
                        loading="confirmReview"
                    />
                </div>
            </form>
        </x-ts-modal>
    </x-slot:modal>
</x-ui::components.record-manager>
