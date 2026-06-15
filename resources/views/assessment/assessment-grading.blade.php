<div>
    <x-mary-header title="Assessment Grading" subtitle="Score student competencies and finalize assessment" separator />

    @if($this->assessment === null)
        <x-mary-card>
            <div class="text-center py-12 text-base-content/40">
                <x-mary-icon name="o-exclamation-triangle" class="size-16 mx-auto mb-4 opacity-30" />
                <p class="text-lg font-medium">No rubric available</p>
                <p class="text-sm">This internship does not have an active rubric defined.</p>
            </div>
        </x-mary-card>
    @else
        <x-mary-card class="mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium">{{ $this->registration->mentee?->user?->name ?? 'Unknown' }}</p>
                    <p class="text-sm text-base-content/60">{{ $this->registration->internship?->name }}</p>
                </div>
                <div class="flex items-center gap-2">
                    @if($this->assessment->finalized_at)
                        <span class="badge badge-success">Finalized</span>
                    @else
                        <span class="badge badge-warning">Draft</span>
                    @endif
                </div>
            </div>
        </x-mary-card>

        @unless($this->isFinalized)
            <div class="flex gap-2 mb-4">
                <x-mary-button label="Auto-Import Scores" icon="o-arrow-down-tray" wire:click="autoImport" class="btn-sm btn-outline" />
            </div>
        @endunless

        @php
            $totalWeightedScore = 0;
            $totalWeight = 0;
            $assessment = $this->assessment;
            $content = $assessment->content ?? [];
            $autoScores = $content['auto'] ?? [];
        @endphp

        @if(count($autoScores) > 0)
            <x-mary-card class="mb-4">
                <h4 class="font-medium mb-2 text-sm">Auto-Imported Scores</h4>
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-3 bg-base-200/50 rounded-xl">
                        <p class="text-xs text-base-content/40">Average Submission Score</p>
                        <p class="text-xl font-bold">{{ $autoScores['avg_submission_score'] ?? '-' }}</p>
                    </div>
                    <div class="p-3 bg-base-200/50 rounded-xl">
                        <p class="text-xs text-base-content/40">Logbook Completeness</p>
                        <p class="text-xl font-bold">{{ $autoScores['logbook_completeness'] ?? '-' }}%</p>
                    </div>
                </div>
            </x-mary-card>
        @endif

        @foreach($this->evaluableCompetencies as $competency)
            <x-mary-card class="mb-3">
                <div class="flex items-center gap-2 mb-3">
                    <h4 class="font-semibold">{{ $competency->name }}</h4>
                    <span class="badge badge-primary badge-sm">{{ $competency->weight }}%</span>
                </div>

                @php
                    $compScore = 0;
                    $compIndicatorWeight = 0;
                @endphp

                @foreach($competency->indicators as $indicator)
                    @php
                        $key = "{$competency->id}.{$indicator->id}";
                        $currentScore = (float) ($this->scores[$key] ?? 0);
                        $normalized = $indicator->max_score > 0 ? ($currentScore / $indicator->max_score) * 100 : 0;
                        $compScore += $normalized * ($indicator->weight / 100);
                        $compIndicatorWeight += $indicator->weight;
                    @endphp

                    <div class="flex items-center gap-4 mb-2">
                        <div class="flex-1">
                            <p class="text-sm">{{ $indicator->name }}</p>
                            <p class="text-xs text-base-content/40">max {{ $indicator->max_score }}, weight {{ $indicator->weight }}%</p>
                        </div>
                        <div class="w-24">
                            <x-mary-input
                                type="number"
                                step="0.1"
                                min="0"
                                :max="$indicator->max_score"
                                placeholder="0-{{ $indicator->max_score }}"
                                wire:model.live="scores.{{ $key }}"
                                :disabled="$isFinalized" />
                        </div>
                    </div>
                @endforeach

                @if($compIndicatorWeight > 0)
                    @php
                        $competencyContribution = $compScore * ($competency->weight / 100);
                        $totalWeightedScore += $competencyContribution;
                        $totalWeight += $competency->weight;
                    @endphp
                    <div class="text-right text-sm text-base-content/60 mt-2">
                        Subtotal: {{ number_format($compScore, 1) }} / 100
                        (contributes {{ number_format($competencyContribution, 1) }}% to final)
                    </div>
                @endif
            </x-mary-card>
        @endforeach

        @foreach($this->readOnlyCompetencies as $competency)
            <x-mary-card class="mb-3 opacity-70">
                <div class="flex items-center gap-2 mb-3">
                    <h4 class="font-semibold">{{ $competency->name }}</h4>
                    <span class="badge badge-ghost badge-sm">{{ $competency->weight }}%</span>
                    <span class="text-xs text-base-content/40">({{ $competency->evaluator_role->label() }} only)</span>
                </div>

                @php
                    $compData = $content['competencies'][$competency->id] ?? [];
                    $compIndicators = $compData['indicators'] ?? [];
                @endphp

                @foreach($competency->indicators as $indicator)
                    @php
                        $indScore = $compIndicators[$indicator->id] ?? '-';
                    @endphp
                    <div class="flex items-center gap-4 mb-2">
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

        @if($totalWeight > 0)
            <x-mary-card>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-lg">Final Score</p>
                        <p class="text-sm text-base-content/60">Weighted total from all evaluable competencies</p>
                    </div>
                    <p class="text-3xl font-bold text-primary">{{ number_format($totalWeightedScore, 1) }}</p>
                </div>
            </x-mary-card>
        @endif

        @unless($this->isFinalized)
            <div class="mt-4 flex justify-end">
                <x-mary-button label="Finalize Assessment" icon="o-lock" wire:click="askFinalize" class="btn-success" />
            </div>
        @endunless
    @endif

    <x-core::ui.confirm message="Finalize this assessment? This action cannot be undone." />
</div>
