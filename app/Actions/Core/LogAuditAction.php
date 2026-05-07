<?php

declare(strict_types=1);

namespace App\Actions\Core;

use App\Support\Logger;
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
    ): void {
        if ($action === '') {
            throw new InvalidArgumentException('Audit action must not be empty.');
        }

        $subject = null;
        if ($subjectType !== null && $subjectId !== null && is_a($subjectType, Model::class, true)) {
            $subject = $subjectType::find($subjectId);
        }

        Logger::info($action)
            ->event($action)
            ->module($module ?? 'system')
            ->withPayload($payload ?? [])
            ->about($subject)
            ->activityOnly()
            ->save();
    }
}
