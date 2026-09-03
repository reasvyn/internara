<div>
    <x-ui::components.page-header :title="__('assessment.grading')" :description="__('assessment.grading_subtitle')" />

    @if ($this->assessment === null)
        <x-ts-card shadowless>
            <div class="text-base-content/40 py-12 text-center">
                <x-ts-icon name="exclamation-triangle" class="mx-auto mb-4 size-16 opacity-30" />
                <p class="text-lg font-medium">{{ __('assessment.no_rubric_available') }}</p>
                <p class="text-sm">{{ __('assessment.no_rubric_desc') }}</p>
            </div>
        </x-ts-card>

    @else
        <x-ts-card shadowless class="mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium">{{ $this->registration->mentee?->user?->name ?? __('common.unknown') }}</p>
                    <p class="text-base-content/60 text-sm">{{ $this->registration->internship?->name }}</p>
                </div>
                <div class="flex items-center gap-2">
                    @if ($this->assessment->finalized_at)
                        <x-ts-badge :text="__('assessment.finalized')" color="green" />
                    @else
                        <x-ts-badge :text="__('assessment.draft')" color="yellow" />
                    @endif
                </div>
            </div>

            @unless ($this->isFinalized)
                <div class="mb-4 flex gap-2">
                    <x-ts-button
                        :text="__('assessment.auto_import')"
                        icon="arrow-down-tray"
                        wire:click="autoImport"
                        color="slate" outline
                        sm
                    />
                </div>
            @endunless

            @php
                $totalWeightedScore = 0;
                $totalWeight = 0;
                $assessment = $this->assessment;
                $content = $assessment->content ?? [];
                $autoScores = $content['auto'] ?? [];
            @endphp

            @if (count($autoScores) > 0)
                <x-ts-card shadowless class="mb-4">
                    <h4 class="mb-2 text-sm font-medium">{{ __('assessment.auto_scores') }}</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-base-200/50 rounded-xl p-3">
                            <p class="text-base-content/40 text-xs">{{ __('assessment.avg_submission_score') }}</p>
                            <p class="text-xl font-bold">{{ $autoScores['avg_submission_score'] ?? '-' }}</p>
                        </div>
                        <div class="bg-base-200/50 rounded-xl p-3">
                            <p class="text-base-content/40 text-xs">{{ __('assessment.logbook_completeness') }}</p>
                            <p class="text-xl font-bold">{{ $autoScores['logbook_completeness'] ?? '-' }}%</p>
                        </div>
                    </div>
                </x-ts-card>
            @endif

            @foreach ($this->evaluableCompetencies as $competency)
                <x-ts-card shadowless class="mb-3">
                    <div class="mb-3 flex items-center gap-2">
                        <h4 class="font-semibold">{{ $competency->name }}</h4>
                        <x-ts-badge :text="$competency->weight.'%'" color="primary" xs />
                    </div>

                    @php
                        $compScore = 0;
                        $compIndicatorWeight = 0;
                    @endphp

                    @foreach ($competency->indicators as $indicator)
                        @php
                            $indWeight = $indicator->weight;
                            $compIndicatorWeight += $indWeight;
                            $currentScore = $this->scores[$indicator->id] ?? 0;
                            $compScore += ($currentScore * $indWeight) / 100;
                        @endphp
                        <div class="mb-3 flex items-center gap-4">
                            <div class="flex-1">
                                <p class="text-sm font-medium">{{ $indicator->name }}</p>
                                @if ($indicator->description)
                                    <p class="text-base-content/40 text-xs">{{ $indicator->description }}</p>
                                @endif
                            </div>
                            <div class="w-16 text-right text-xs">
                                <span class="text-base-content/40">{{ $indWeight }}%</span>
                            </div>
                            <div class="w-32">
                                <x-ts-input
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.1"
                                    wire:model.live="scores.{{ $indicator->id }}"
                                    :disabled="$this->isFinalized"
                                    sm
                                />
                            </div>
                        </div>
                    @endforeach

                    @php
                        $compFinalScore = $compIndicatorWeight > 0 ? ($compScore / $compIndicatorWeight) * 100 : 0;
                        $totalWeightedScore += ($compFinalScore * $competency->weight) / 100;
                        $totalWeight += $competency->weight;
                    @endphp

                    <div class="border-base-200 mt-2 flex justify-between border-t pt-2 text-sm">
                        <span class="text-base-content/60">{{ __('assessment.competency_score') }}</span>
                        <span class="font-bold">{{ number_format($compFinalScore, 1) }}</span>
                    </div>
                </x-ts-card>
            @endforeach

            @foreach ($this->readOnlyCompetencies as $competency)
                <x-ts-card shadowless class="mb-3 opacity-70">
                    <div class="mb-3 flex items-center gap-2">
                        <h4 class="font-semibold">{{ $competency->name }}</h4>
                        <x-ts-badge :text="$competency->weight.'%'" color="gray" xs />
                        <span class="text-base-content/40 text-xs">({{ $competency->evaluator_role->label() }} only)</span>
                    </div>

                    @php
                        $compData = $content['competencies'][$competency->id] ?? [];
                        $compIndicators = $compData['indicators'] ?? [];
                    @endphp

                    @foreach ($competency->indicators as $indicator)
                        @php
                            $indScore = $compIndicators[$indicator->id] ?? '-';
                        @endphp
                        <div class="mb-2 flex items-center gap-4">
                            <div class="flex-1">
                                <p class="text-sm">{{ $indicator->name }}</p>
                            </div>
                            <div class="w-24 text-right font-medium">
                                {{ $indScore === '-' ? '-' : $indScore . ' / ' . $indicator->max_score }}
                            </div>
                        </div>
                    @endforeach
                </x-ts-card>

            @endforeach

            @if ($totalWeight > 0)
                <x-ts-card shadowless>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-lg font-semibold">{{ __('assessment.final_score') }}</p>
                            <p class="text-base-content/60 text-sm">{{ __('assessment.weighted_total_desc') }}</p>
                        </div>
                        <p class="text-primary text-3xl font-bold">{{ number_format($totalWeightedScore, 1) }}</p>
                    </div>
                </x-ts-card>

            @endif

            @unless ($this->isFinalized)
                <div class="mt-4 flex justify-end">
                    <x-ts-button :text="__('assessment.finalize')" icon="lock" wire:click="askFinalize" color="green" />
                </div>
            @endunless
        </x-ts-card>

    @endif
</div>
