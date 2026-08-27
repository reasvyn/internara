<div>
    <x-ui::ui.page-header
        :title="__('assessment.my_assessments')"
        :description="__('assessment.my_assessments_subtitle')"
    />

    @forelse ($this->assessments as $assessment)
        <x-ts-card shadowless class="mb-4">
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <p class="font-medium">
                        {{ $assessment->registration?->internship?->name ?? __('common.unknown') }}
                    </p>
                    <p class="text-base-content/60 text-sm">
                        Finalized {{ $assessment->finalized_at->format('d M Y') }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-primary text-3xl font-bold">{{ number_format($assessment->score ?? 0, 1) }}</p>
                    <p class="text-base-content/40 text-xs">{{ __('assessment.final_score') }}</p>
                </div>
            </div>

            @if ($assessment->rubric)
                <div class="divider text-base-content/40 my-2 text-xs">{{ __('assessment.competencies') }}</div>

                @php
                    $content = $assessment->content ?? [];
                    $competenciesData = $content['competencies'] ?? [];
                @endphp

                @foreach ($assessment->rubric->competencies as $competency)
                    @php
                        $compData = $competenciesData[$competency->id] ?? [];
                        $indicatorsData = $compData['indicators'] ?? [];
                        $compScore = 0;
                        $indicatorCount = 0;
                    @endphp

                    <div class="bg-base-200/50 mb-3 rounded-xl p-3">
                        <div class="mb-2 flex items-center gap-2">
                            <p class="text-sm font-medium">{{ $competency->name }}</p>
                            <x-ts-badge :text="$competency->weight.'%'" color="white" xs />
                        </div>

                        @foreach ($competency->indicators as $indicator)
                            @php
                                $score = $indicatorsData[$indicator->id] ?? null;
                                $indicatorCount++;
                                if ($score !== null && $indicator->max_score > 0) {
                                    $compScore += ($score / $indicator->max_score) * 100 * ($indicator->weight / 100);
                                }
                            @endphp
                            <div class="flex items-center justify-between py-1 text-sm">
                                <span>{{ $indicator->name }}</span>
                                <span class="font-medium">
                                    {{ $score !== null ? $score . ' / ' . $indicator->max_score : '-' }}
                                </span>
                            </div>
                        @endforeach

                        @if ($indicatorCount > 0)
                            <div class="text-base-content/50 mt-1 text-right text-xs">
                                {{ __('assessment.competency_score') }}: {{ number_format($compScore, 1) }} / 100
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif

            @if ($assessment->feedback)
                <div class="bg-base-100 mt-2 rounded-xl p-3">
                    <p class="text-base-content/40 mb-1 text-xs">{{ __('assessment.feedback') }}</p>
                    <p class="text-sm">{{ $assessment->feedback }}</p>
                </div>
            @endif

    @empty
        <x-ts-card shadowless>
            <div class="text-base-content/40 py-12 text-center">
                <x-ts-icon name="document-text" class="mx-auto mb-4 size-16 opacity-30" />
                <p class="text-lg font-medium">{{ __('assessment.no_assessments_yet') }}</p>
                <p class="text-sm">{{ __('assessment.no_assessments_desc') }}</p>
            </div>

    @endforelse
</div>
