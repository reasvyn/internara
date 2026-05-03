declare(strict_types=1);

namespace App\Domain\Core\Actions;

use App\Domain\Core\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Stateless Action to log system and user audit events.
 *
 * S1 - Secure: Centralized logging for forensic analysis.
 * S3 - Scalable: Stateless and can be called from any entry point.
 */
class LogAuditAction
{
    /**
     * Execute the audit logging.
     */
    public function execute(
        string $action,
        ?string $subjectType = null,
        ?string $subjectId = null,
        ?array $payload = null,
        ?string $module = null,
    ): AuditLog {
        // Here we could add PII masking logic

        return AuditLog::create([
            'user_id' => Auth::id(),
            'subject_id' => $subjectId,
            'subject_type' => $subjectType,
            'action' => $action,
            'payload' => $payload,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'module' => $module,
        ]);
    }
}
