<?php

declare(strict_types=1);

namespace App\Actions\Core;

use App\Support\SmartLogger;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class LogAuditAction
{
    public function execute(
        string $action,
        ?string $subjectType = null,
        ?string $subjectId = null,
        ?array $payload = null,
        ?string $module = null,
        mixed $user = null,
        bool $maskPii = false,
    ): void {
        if ($action === '') {
            throw new InvalidArgumentException('Audit action must not be empty.');
        }

        $subject = null;
        if ($subjectType !== null && $subjectId !== null && is_a($subjectType, Model::class, true)) {
            $subject = $subjectType::find($subjectId);
        }

        $log = SmartLogger::info($action)
            ->event($action)
            ->module($module ?? 'system')
            ->withPayload($payload ?? [])
            ->about($subject)
            ->activityOnly();

        if ($maskPii) {
            $log->withPiiMasking();
        }

        $log->save();
    }
}
