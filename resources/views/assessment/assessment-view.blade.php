<div>
    <x-mary-header :title="__('assessment.my_assessments')" :subtitle="__('assessment.my_assessments_subtitle')" separator />

    @forelse($this->assessments as $assessment)
        <x-mary-card class="mb-4">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <p class="font-medium">{{ $assessment->registration?->internship?->name ?? 'Internship' }}</p>
                    <p class="text-sm text-base-content/60">Finalized {{ $assessment->finalized_at->format('d M Y') }}</p>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-bold text-primary">{{ number_format($assessment->score ?? 0, 1) }}</p>
                    <p class="text-xs text-base-content/40">{{ __('assessment.final_score') }}</p>
                </div>
            </div>

            @if($assessment->rubric)
                <div class="divider text-xs text-base-content/40 my-2">{{ __('assessment.competencies') }}</div>

                @php
                    $content = $assessment->content ?? [];
                    $competenciesData = $content['competencies'] ?? [];
                @endphp

                @foreach($assessment->rubric->competencies as $competency)
                    @php
                        $compData = $competenciesData[$competency->id] ?? [];
                        $indicatorsData = $compData['indicators'] ?? [];
                        $compScore = 0;
                        $indicatorCount = 0;
                    @endphp

                    <div class="mb-3 p-3 bg-base-200/50 rounded-xl">
                        <div class="flex items-center gap-2 mb-2">
                            <p class="font-medium text-sm">{{ $competency->name }}</p>
                            <span class="badge badge-ghost badge-xs">{{ $competency->weight }}%</span>
                        </div>

                        @foreach($competency->indicators as $indicator)
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

                        @if($indicatorCount > 0)
                            <div class="text-right text-xs text-base-content/50 mt-1">
                                {{ __('assessment.competency_score') }}: {{ number_format($compScore, 1) }} / 100
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif

            @if($assessment->feedback)
                <div class="mt-2 p-3 bg-base-100 rounded-xl">
                    <p class="text-xs text-base-content/40 mb-1">{{ __('assessment.feedback') }}</p>
                    <p class="text-sm">{{ $assessment->feedback }}</p>
                </div>
            @endif
        </x-mary-card>
    @empty
        <x-mary-card>
            <div class="text-center py-12 text-base-content/40">
                <x-mary-icon name="o-document-text" class="size-16 mx-auto mb-4 opacity-30" />
                <p class="text-lg font-medium">{{ __('assessment.no_assessments_yet') }}</p>
                <p class="text-sm">{{ __('assessment.no_assessments_desc') }}</p>
            </div>
        </x-mary-card>
    @endforelse
</div>
