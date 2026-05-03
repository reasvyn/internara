declare(strict_types=1);

namespace App\Domain\Mentor\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\User\Models\User;
use App\Domain\Mentor\Models\SupervisionLog;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateSupervisionLogAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(User $teacher, array $data): SupervisionLog
    {
        return DB::transaction(function () use ($teacher, $data) {
            if (! isset($data['registration_id'])) {
                throw new RuntimeException('Registration ID is required.');
            }

            if (! isset($data['type'])) {
                throw new RuntimeException('Supervision type is required.');
            }

            $log = SupervisionLog::create([
                'registration_id' => $data['registration_id'],
                'supervisor_id' => $teacher->id,
                'type' => $data['type'],
                'date' => $data['date'] ?? now()->toDateString(),
                'topic' => $data['topic'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'in_progress',
            ]);

            $this->logAudit->execute(
                action: 'supervision_log_created',
                subjectType: SupervisionLog::class,
                subjectId: $log->id,
                payload: ['type' => $log->type, 'topic' => $log->topic],
                module: 'Supervision',
            );

            return $log;
        });
    }
}
