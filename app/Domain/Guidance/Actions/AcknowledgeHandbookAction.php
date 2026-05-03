declare(strict_types=1);

namespace App\Domain\Guidance\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\User\Models\User;
use App\Domain\Guidance\Models\Handbook;

/**
 * Records user acknowledgement of a handbook.
 *
 * S1 - Secure: Creates immutable audit trail for compliance.
 */
class AcknowledgeHandbookAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(User $user, Handbook $handbook): void
    {
        $handbook->acknowledgements()->create([
            'user_id' => $user->id,
            'acknowledged_at' => now(),
            'ip_address' => request()->ip(),
        ]);

        $this->logAudit->execute(
            action: 'handbook_acknowledged',
            subjectType: Handbook::class,
            subjectId: $handbook->id,
            payload: ['user_id' => $user->id],
            module: 'Guidance',
        );
    }
}
