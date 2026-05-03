declare(strict_types=1);

namespace App\Domain\Assessment\Actions;

use App\Domain\Assessment\Models\Assessment;

/**
 * Stateless Action to finalize an assessment.
 *
 * S1 - Secure: Prevents further modifications after finalization.
 * S2 - Sustain: Status transition logic.
 */
class FinalizeAssessmentAction
{
    public function execute(Assessment $assessment): Assessment
    {
        if ($assessment->isFinalized()) {
            throw new \InvalidArgumentException('Assessment already finalized.');
        }

        $assessment->update([
            'finalized_at' => now(),
        ]);

        return $assessment->fresh();
    }
}
