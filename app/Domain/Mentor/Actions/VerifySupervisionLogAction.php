declare(strict_types=1);

namespace App\Domain\Mentor\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\User\Models\User;
use App\Enums\SupervisionLogStatus;
use App\Domain\Mentor\Models\SupervisionLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VerifySupervisionLogAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(SupervisionLog $log, User $verifier): SupervisionLog
    {
        if ($log->is_verified) {
            throw new RuntimeException('This supervision log has already been verified.');
        }

        return DB::transaction(function () use ($log, $verifier) {
            $log->update([
                'is_verified' => true,
                'verified_by' => $verifier->id,
                'verified_at' => Carbon::now(),
                'status' => SupervisionLogStatus::VERIFIED->value,
            ]);

            $this->logAudit->execute(
                action: 'supervision_log_verified',
                subjectType: SupervisionLog::class,
                subjectId: $log->id,
                payload: ['verifier' => $verifier->name],
                module: 'Supervision',
            );

            return $log;
        });
    }
}
