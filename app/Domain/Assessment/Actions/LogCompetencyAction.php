declare(strict_types=1);

namespace App\Domain\Assessment\Actions;

use App\Domain\Mentee\Models\CompetencyLog;
use App\Domain\Assessment\Models\Competency;

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
    ): CompetencyLog {
        $competency = Competency::findOrFail($competencyId);

        $log = CompetencyLog::create([
            'registration_id' => $registrationId,
            'competency_id' => $competency->id,
            'evaluator_id' => $evaluatorId,
            'score' => $score,
            'notes' => $notes,
        ]);

        return $log;
    }
}
