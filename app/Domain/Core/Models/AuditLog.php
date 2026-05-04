<?php

declare(strict_types=1);

namespace App\Domain\Core\Models;

use App\Domain\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use LogicException;

/**
 * Immutable audit trail for system and user actions.
 *
 * S1 - Secure: Forensic record of all state-changing operations.
 * S2 - Sustain: Centralized audit logging with structured payload.
 */
#[Fillable(['user_id', 'subject_id', 'subject_type', 'action', 'payload', 'ip_address', 'user_agent', 'module'])]
class AuditLog extends Model
{
    use HasUuid;

    public $timestamps = true;

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Prevent updates and deletions to maintain forensic integrity.
     */
    protected static function booted(): void
    {
        static::updating(function (self $model): never {
            Log::critical('Attempted to update immutable audit log entry', [
                'audit_log_id' => $model->getKey(),
                'action' => $model->action,
            ]);

            throw new LogicException('Audit log entries are immutable and cannot be updated.');
        });

        static::deleting(function (self $model): never {
            Log::critical('Attempted to delete immutable audit log entry', [
                'audit_log_id' => $model->getKey(),
                'action' => $model->action,
            ]);

            throw new LogicException('Audit log entries are immutable and cannot be deleted.');
        });
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by module.
     */
    public function scopeForModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    /**
     * Scope to filter by action.
     */
    public function scopeOfAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by subject type.
     */
    public function scopeForSubject(Builder $query, string $type): Builder
    {
        return $query->where('subject_type', $type);
    }

    /**
     * Scope to order by most recent first.
     */
    public function scopeRecent(Builder $query, int $limit = 50): Builder
    {
        return $query->latest()->limit($limit);
    }
}
