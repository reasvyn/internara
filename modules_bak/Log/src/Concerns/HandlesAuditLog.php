<?php

declare(strict_types=1);

namespace Modules\Log\Concerns;

use Illuminate\Support\Facades\Auth;
use Modules\Log\Models\AuditLog;

/**
 * Trait HandlesAuditLog
 *
 * Provides utilities for logging critical data modifications.
 */
trait HandlesAuditLog
{
    /**
     * Record an administrative action in the audit log.
     */
    public function recordAuditLog(string $action, ?array $payload = null): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'subject_id' => (string) $this->getKey(),
            'subject_type' => $this->getMorphClass(),
            'action' => $action,
            'payload' => $payload ?? $this->getDirty(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Boot the audit log trait.
     */
    protected static function bootHandlesAuditLog(): void
    {
        static::updated(function ($model) {
            $model->recordAuditLog('updated');
        });

        static::deleted(function ($model) {
            $model->recordAuditLog('deleted', $model->toArray());
        });
    }
}
