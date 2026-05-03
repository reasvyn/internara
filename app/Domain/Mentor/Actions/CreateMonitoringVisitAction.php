declare(strict_types=1);

namespace App\Domain\Mentor\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\User\Models\User;
use App\Domain\Mentor\Models\MonitoringVisit;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateMonitoringVisitAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(User $teacher, array $data): MonitoringVisit
    {
        return DB::transaction(function () use ($teacher, $data) {
            if (! isset($data['registration_id'])) {
                throw new RuntimeException('Registration ID is required.');
            }

            $visit = MonitoringVisit::create([
                'registration_id' => $data['registration_id'],
                'teacher_id' => $teacher->id,
                'date' => $data['date'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'company_feedback' => $data['company_feedback'] ?? null,
                'student_condition' => $data['student_condition'] ?? null,
                'status' => 'completed',
            ]);

            $this->logAudit->execute(
                action: 'monitoring_visit_created',
                subjectType: MonitoringVisit::class,
                subjectId: $visit->id,
                payload: ['date' => $visit->date],
                module: 'Supervision',
            );

            return $visit;
        });
    }
}
