<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Actions;

use App\Modules\Assessment\Data\SubmitPartnerAssessmentData;
use App\Modules\Assessment\Models\Assessment;
use App\Modules\Assessment\Models\AssessmentMagicLink;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Data\ActionResponse;
use App\Modules\Core\Exceptions\RejectedException;

final class SubmitPartnerAssessmentAction extends BaseCommandAction
{
    public function __construct(
        private readonly InitializeAssessmentAction $initializeAction,
    ) {}

    public function execute(SubmitPartnerAssessmentData $data): ActionResponse
    {
        $magicLink = AssessmentMagicLink::where('token', $data->token)->first();

        if ($magicLink === null) {
            throw new RejectedException(__('assessment.magic_link_not_found'));
        }

        if ($magicLink->isExpired()) {
            throw new RejectedException(__('assessment.magic_link_expired'));
        }

        if ($magicLink->isSubmitted()) {
            throw new RejectedException(__('assessment.magic_link_already_submitted'));
        }

        return $this->transaction(function () use ($magicLink, $data): ActionResponse {
            $initResult = $this->initializeAction->execute($magicLink->registration_id);
            $assessment = $initResult['assessment'] ?? Assessment::where('registration_id', $magicLink->registration_id)->first();

            if ($assessment !== null) {
                $scoresData = $assessment->scores_data ?? [];
                $scoresData['competencies'] ??= [];
                $evaluatedAt = now()->toIso8601String();

                foreach ($data->scores as $compId => $indicators) {
                    if (! is_array($indicators)) {
                        continue;
                    }

                    $scoresData['competencies'][$compId]['evaluator_role'] = 'supervisor';
                    $scoresData['competencies'][$compId]['evaluator_name'] = $data->evaluatorName;
                    $scoresData['competencies'][$compId]['evaluated_at'] = $evaluatedAt;

                    foreach ($indicators as $indId => $score) {
                        if (is_numeric($score)) {
                            $scoresData['competencies'][$compId]['indicators'][$indId] = (float) $score;
                        }
                    }
                }

                $assessment->update([
                    'scores_data' => $scoresData,
                    'feedback' => $data->feedback ?: $assessment->feedback,
                ]);
            }

            $magicLink->update([
                'submitted_at' => now(),
                'evaluator_name' => $data->evaluatorName,
                'evaluator_phone' => $data->evaluatorPhone,
                'feedback' => $data->feedback,
            ]);

            $this->log('partner_assessment_submitted', $magicLink, [
                'registration_id' => $magicLink->registration_id,
                'evaluator_name' => $data->evaluatorName,
                'submitted_at' => $magicLink->submitted_at?->toIso8601String(),
            ]);

            return ActionResponse::updated($magicLink->fresh());
        });
    }
}
