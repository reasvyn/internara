<div class="p-8">
    <x-mary-header title="{{ __('evaluation.page_title') }}" subtitle="{{ __('evaluation.page_subtitle') }}" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button label="{{ __('evaluation.new_evaluation') }}" icon="o-plus" class="btn-primary" wire:click="create" />
        </x-slot:actions>
    </x-mary-header>

    {{-- Type filter --}}
    <div class="mb-4 flex gap-2">
        <x-mary-button
            label="{{ __('evaluation.all_types') }}"
            wire:click="$set('filterType', '')"
            :class="!$filterType ? 'btn-primary btn-sm' : 'btn-ghost btn-sm'"
        />
        @foreach ($this->typeOptions as $opt)
            <x-mary-button
                :label="$opt['name']"
                wire:click="$set('filterType', '{{ $opt['id'] }}')"
                :class="$filterType === $opt['id'] ? 'btn-primary btn-sm' : 'btn-ghost btn-sm'"
            />
        @endforeach
    </div>

    @if ($showForm)
        <x-mary-card shadow class="bg-base-100 border border-base-200 mb-6">
            <form wire:submit="{{ $editingEvaluation ? 'update' : 'store' }}">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-mary-select
                        label="{{ __('evaluation.evaluation_type') }}"
                        wire:model.live="evaluationType"
                        :options="$this->typeOptions"
                        option-value="id"
                        option-label="name"
                        required
                    />

                    @if ($evaluationType === 'mentor')
                        <x-mary-select
                            label="{{ __('evaluation.mentor') }}"
                            wire:model="mentorId"
                            :options="$mentors->map(fn ($m) => ['id' => $m->id, 'name' => $m->name])"
                            option-value="id"
                            option-label="name"
                            placeholder="{{ __('evaluation.select_mentor') }}"
                            required
                        />
                    @endif

                    <x-mary-input
                        label="{{ __('evaluation.overall_score') }}"
                        type="number"
                        step="0.1"
                        min="0"
                        max="100"
                        wire:model="overallScore"
                        placeholder="0 - 100"
                        required
                    />
                </div>

                <div class="mt-4">
                    <label class="label">
                        <span class="label-text font-medium">{{ __('evaluation.criteria_scores') }} <span class="text-xs opacity-50">(0 - 100)</span></span>
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach ($this->criteriaLabels as $key => $label)
                            <x-mary-input
                                :label="$label"
                                type="number"
                                step="0.1"
                                min="0"
                                max="100"
                                wire:model="criteriaScores.{{ $key }}"
                            />
                        @endforeach
                    </div>
                </div>

                <x-mary-textarea
                    label="{{ __('evaluation.feedback') }}"
                    wire:model="feedback"
                    placeholder="{{ __('evaluation.feedback_placeholder') }}"
                    rows="4"
                    class="mt-4"
                />

                <div class="flex justify-end gap-2 mt-4">
                    <x-mary-button label="{{ __('evaluation.cancel') }}" wire:click="cancel" />
                    <x-mary-button
                        :label="$editingEvaluation ? __('evaluation.update') : __('evaluation.submit')"
                        type="submit"
                        class="btn-primary"
                        spinner
                    />
                </div>
            </form>
        </x-mary-card>
    @endif

    <x-mary-card shadow class="bg-base-100 border border-base-200">
        @if ($evaluations->isEmpty())
            <div class="text-center py-8 opacity-60">
                <x-mary-icon name="o-clipboard-document-check" class="w-12 h-12 mx-auto mb-3" />
                <p class="text-lg">{{ __('evaluation.no_evaluations') }}</p>
                <p class="text-sm">{{ __('evaluation.no_evaluations_hint') }}</p>
            </div>
        @else
            @php
                $headers = [
                    ['key' => 'type', 'label' => __('evaluation.type')],
                    ['key' => 'target', 'label' => __('evaluation.target')],
                    ['key' => 'overall_score', 'label' => __('evaluation.score')],
                    ['key' => 'evaluator', 'label' => __('evaluation.evaluator')],
                    ['key' => 'created_at', 'label' => __('evaluation.date')],
                    ['key' => 'actions', 'label' => ''],
                ];
            @endphp

            <x-mary-table :headers="$headers" :rows="$evaluations" with-pagination>
                @scope('cell_type', $evaluation)
                    <x-mary-badge :value="$evaluation->evaluation_type?->label() ?? $evaluation->evaluation_type" class="badge-outline badge-sm" />
                @endscope

                @scope('cell_target', $evaluation)
                    @if ($evaluation->evaluation_type->value === 'mentor' && $evaluation->mentor)
                        <div>
                            <div class="font-medium">{{ $evaluation->mentor->name }}</div>
                            <div class="text-xs opacity-50">{{ __('evaluation.mentor') }}</div>
                        </div>
                    @elseif ($evaluation->evaluation_type->value === 'program')
                        <div>
                            <div class="font-medium">{{ __('evaluation.program') }}</div>
                            <div class="text-xs opacity-50">{{ $evaluation->registration?->internship?->name ?? '-' }}</div>
                        </div>
                    @else
                        <span class="opacity-50">-</span>
                    @endif
                @endscope

                @scope('cell_overall_score', $evaluation)
                    <x-mary-badge
                        :value="$evaluation->overall_score"
                        :class="$evaluation->overall_score >= 80 ? 'badge-success' : ($evaluation->overall_score >= 60 ? 'badge-warning' : 'badge-error')"
                    />
                @endscope

                @scope('cell_evaluator', $evaluation)
                    {{ $evaluation->evaluator->name }}
                @endscope

                @scope('cell_created_at', $evaluation)
                    {{ $evaluation->created_at->format('d M Y') }}
                @endscope

                @scope('cell_actions', $evaluation)
                    <div class="flex gap-2">
                        <x-mary-button icon="o-eye" class="btn-ghost btn-sm" wire:click="edit('{{ $evaluation->id }}')" />
                        <x-mary-button
                            icon="o-trash"
                            class="btn-ghost btn-sm text-error"
                            wire:click="delete('{{ $evaluation->id }}')"
                            wire:confirm="{{ __('evaluation.confirm_delete') }}"
                        />
                    </div>
                @endscope
            </x-mary-table>
        @endif
    </x-mary-card>
</div>
