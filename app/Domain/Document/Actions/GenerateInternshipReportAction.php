
declare(strict_types=1);

namespace App\Domain\Document\Actions;

use App\Domain\Internship\Models\Registration;
use Illuminate\Support\Facades\DB;

/**
 * Stateless Action to generate student internship report.
 *
 * S2 - Sustain: Aggregates journal entries and assessments.
 */
class GenerateInternshipReportAction
{
    public function execute(string $registrationId): array
    {
        $registration = Registration::with(['student', 'internship'])->findOrFail(
            $registrationId,
        );

        // Get journal statistics
        $journalStats = DB::table('journal_entries')
            ->where('registration_id', $registrationId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        // Get assessment scores
        $assessments = DB::table('assessments')
            ->where('registration_id', $registrationId)
            ->selectRaw('type, AVG(score) as avg_score')
            ->groupBy('type')
            ->get();

        // Get competency logs
        $competencyLogs = DB::table('student_competency_logs')
            ->join('competencies', 'student_competency_logs.competency_id', '=', 'competencies.id')
            ->where('student_competency_logs.registration_id', $registrationId)
            ->select(
                'competencies.name',
                'student_competency_logs.score',
                'student_competency_logs.notes',
            )
            ->get();

        return [
            'student' => $registration->student->name,
            'internship' => $registration->internship->name,
            'journal_stats' => $journalStats,
            'assessments' => $assessments,
            'competencies' => $competencyLogs,
            'total_journals' => array_sum($journalStats),
            'verified_journals' => $journalStats['verified'] ?? 0,
        ];
    }
}
