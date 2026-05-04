<?php

declare(strict_types=1);

namespace App\Domain\Core\Actions;

use App\Domain\Core\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use InvalidArgumentException;

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
     *
     * @throws InvalidArgumentException When action is empty
     */
    public function execute(
        string $action,
        ?string $subjectType = null,
        ?string $subjectId = null,
        ?array $payload = null,
        ?string $module = null,
    ): AuditLog {
        if ($action === '') {
            throw new InvalidArgumentException('Audit action must not be empty.');
        }

        $userId = Auth::id();

        if ($userId === null && app()->runningUnitTests() === false) {
            Log::warning('Audit log created without authenticated user', [
                'action' => $action,
                'subject_type' => $subjectType,
                'module' => $module,
            ]);
        }

        try {
            return AuditLog::create([
                'user_id' => $userId,
                'subject_id' => $subjectId,
                'subject_type' => $subjectType,
                'action' => $action,
                'payload' => $payload,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'module' => $module,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to create audit log entry', [
                'action' => $action,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'module' => $module,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
