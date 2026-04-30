<?php

declare(strict_types=1);

namespace App\Actions\Assessment;

use App\Models\Competency;
use App\Models\StudentCompetencyLog;

/**
 * Stateless Action to log student competency assessment.
 *
 * S2 - Sustain: Tracks competency progress.
 */
class LogCompetencyAction
{
    public function execute(
        string $registrationId,
        string $competencyId,
        string $evaluatorId,
        float $score,
        ?string $notes = null,
    ): StudentCompetencyLog {
        $competency = Competency::findOrFail($competencyId);

        $log = StudentCompetencyLog::create([
            'registration_id' => $registrationId,
            'competency_id' => $competency->id,
            'evaluator_id' => $evaluatorId,
            'score' => $score,
            'notes' => $notes,
        ]);

        return $log;
    }
}
