<?php

declare(strict_types=1);

namespace App\Actions\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use InvalidArgumentException;
use Spatie\Activitylog\Models\Activity;

class LogAuditAction
{
    public function execute(
        string $action,
        ?string $subjectType = null,
        ?string $subjectId = null,
        ?array $payload = null,
        ?string $module = null,
        mixed $user = null,
    ): Activity {
        if ($action === '') {
            throw new InvalidArgumentException('Audit action must not be empty.');
        }

        $user = Auth::user();

        try {
            $activity = activity()
                ->causedBy($user)
                ->event($action)
                ->withProperties([
                    'payload' => $payload,
                    'ip_address' => Request::ip(),
                    'user_agent' => Request::userAgent(),
                ]);

            if ($module !== null) {
                $activity->useLog($module);
            }

            if ($subjectType !== null && $subjectId !== null && is_a($subjectType, Model::class, true)) {
                $subject = $subjectType::find($subjectId);
                if ($subject !== null) {
                    $activity->performedOn($subject);
                }
            }

            return $activity->log($action);
        } catch (\Throwable $e) {
            Log::error('Failed to create activity log entry', [
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
