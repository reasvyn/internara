<div>
    <x-mary-header :title="__('assessment.grading')" :subtitle="__('assessment.grading_subtitle')" separator />

    @if ($this->assessment === null)
        <x-mary-card>
            <div class="text-base-content/40 py-12 text-center">
                <x-mary-icon name="o-exclamation-triangle" class="mx-auto mb-4 size-16 opacity-30" />
                <p class="text-lg font-medium">{{ __('assessment.no_rubric_available') }}</p>
                <p class="text-sm">{{ __('assessment.no_rubric_desc') }}</p>
            </div>
        </x-mary-card>
    @else
        <x-mary-card class="mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium">{{ $this->registration->mentee?->user?->name ?? __('common.unknown') }}</p>
                    <p class="text-base-content/60 text-sm">{{ $this->registration->internship?->name }}</p>
                </div>
                <div class="flex items-center gap-2">
                    @if ($this->assessment->finalized_at)
                        <span class="badge badge-success">{{ __('assessment.finalized') }}</span>
                    @else
                        <span class="badge badge-warning">{{ __('assessment.draft') }}</span>
                    @endif
                </div>
            </div>
        </x-mary-card>

        @unless ($this->isFinalized)
            <div class="mb-4 flex gap-2">
                <x-mary-button
                    :label="__('assessment.auto_import')"
                    icon="o-arrow-down-tray"
                    wire:click="autoImport"
                    class="btn-sm btn-outline"
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
            <x-mary-card class="mb-4">
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
            </x-mary-card>
        @endif

        @foreach ($this->evaluableCompetencies as $competency)
            <x-mary-card class="mb-3">
                <div class="mb-3 flex items-center gap-2">
                    <h4 class="font-semibold">{{ $competency->name }}</h4>
                    <span class="badge badge-primary badge-sm">{{ $competency->weight }}%</span>
                </div>

                @php
                    $compScore = 0;
                    $compIndicatorWeight = 0;
                @endphp

                @foreach ($competency->indicators as $indicator)
                    @php
                        $key = "{$competency->id}.{$indicator->id}";
                        $currentScore = (float) ($this->scores[$key] ?? 0);
                        $normalized = $indicator->max_score > 0 ? ($currentScore / $indicator->max_score) * 100 : 0;
                        $compScore += $normalized * ($indicator->weight / 100);
                        $compIndicatorWeight += $indicator->weight;
                    @endphp

                    <div class="mb-2 flex items-center gap-4">
                        <div class="flex-1">
                            <p class="text-sm">{{ $indicator->name }}</p>
                            <p class="text-base-content/40 text-xs">
                                max {{ $indicator->max_score }}, weight {{ $indicator->weight }}%
                            </p>
                        </div>
                        <div class="w-24">
                            <x-mary-input
                                type="number"
                                step="0.1"
                                min="0"
                                :max="$indicator->max_score"
                                placeholder="0-{{ $indicator->max_score }}"
                                wire:model.live="scores.{{ $key }}"
                                :disabled="$isFinalized"
                            />
                        </div>
                    </div>
                @endforeach

                @if ($compIndicatorWeight > 0)
                    @php
                        $competencyContribution = $compScore * ($competency->weight / 100);
                        $totalWeightedScore += $competencyContribution;
                        $totalWeight += $competency->weight;
                    @endphp
                    <div class="text-base-content/60 mt-2 text-right text-sm">
                        {{ __('assessment.subtotal') }}: {{ number_format($compScore, 1) }} / 100 ({{ __('assessment.contributes', ['pct' => number_format($competencyContribution, 1)]) }})
                    </div>
                @endif
            </x-mary-card>
        @endforeach

        @foreach ($this->readOnlyCompetencies as $competency)
            <x-mary-card class="mb-3 opacity-70">
                <div class="mb-3 flex items-center gap-2">
                    <h4 class="font-semibold">{{ $competency->name }}</h4>
                    <span class="badge badge-ghost badge-sm">{{ $competency->weight }}%</span>
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
            </x-mary-card>
        @endforeach

        @if ($totalWeight > 0)
            <x-mary-card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-lg font-semibold">{{ __('assessment.final_score') }}</p>
                        <p class="text-base-content/60 text-sm">{{ __('assessment.weighted_total_desc') }}</p>
                    </div>
                    <p class="text-primary text-3xl font-bold">{{ number_format($totalWeightedScore, 1) }}</p>
                </div>
            </x-mary-card>
        @endif

        @unless ($this->isFinalized)
            <div class="mt-4 flex justify-end">
                <x-mary-button
                    :label="__('assessment.finalize')"
                    icon="o-lock"
                    wire:click="askFinalize"
                    class="btn-success"
                />
            </div>
        @endunless
    @endif
</div>
