<?php

declare(strict_types=1);

namespace App\Domain\Core\Models\Concerns;

use App\Domain\Core\Support\SmartLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Automatically logs model create/update/delete events via SmartLogger.
 *
 * Usage:
 *   use HasAuditTrail;
 *
 *   protected function auditEvents(): array
 *   {
 *       return ['created', 'updated', 'deleted'];
 *   }
 *
 *   protected function auditModule(): string
 *   {
 *       return 'User';
 *   }
 */
trait HasAuditTrail
{
    public static function bootHasAuditTrail(): void
    {
        static::created(function (Model $model): void {
            if (in_array('created', $model->auditEvents(), true)) {
                $model->writeAudit('created', $model->auditContext());
            }
        });

        static::updated(function (Model $model): void {
            if (in_array('updated', $model->auditEvents(), true)) {
                $changes = [
                    'old' => $model->getOriginal(),
                    'new' => $model->getChanges(),
                ];

                $model->writeAudit('updated', array_merge(
                    $model->auditContext(),
                    ['changes' => $changes],
                ));
            }
        });

        static::deleted(function (Model $model): void {
            if (in_array('deleted', $model->auditEvents(), true)) {
                $model->writeAudit('deleted', $model->auditContext());
            }
        });

        static::restored(function (Model $model): void {
            if (in_array('restored', $model->auditEvents(), true)) {
                $model->writeAudit('restored', $model->auditContext());
            }
        });

        static::forceDeleted(function (Model $model): void {
            if (in_array('forceDeleted', $model->auditEvents(), true)) {
                $model->writeAudit('force_deleted', $model->auditContext());
            }
        });
    }

    /**
     * Define which lifecycle events should be audited.
     * Override in your model to customize.
     *
     * @return string[] Available: created, updated, deleted, restored, forceDeleted
     */
    protected function auditEvents(): array
    {
        return ['created', 'updated', 'deleted'];
    }

    /**
     * Define the module/domain name for the audit log.
     * Override in your model for custom naming.
     */
    protected function auditModule(): string
    {
        $class = static::class;

        if (preg_match('#App\\\\Domain\\\\(\\w+)\\\\#', $class, $matches)) {
            return $matches[1];
        }

        return class_basename($class);
    }

    /**
     * Build context payload for the audit event.
     * Override in your model to include additional data.
     */
    protected function auditContext(): array
    {
        return [];
    }

    /**
     * Whether to mask PII in audit logs for this model.
     * Override to return true when the model handles sensitive data.
     */
    protected function auditMaskPii(): bool
    {
        return false;
    }

    protected function writeAudit(string $event, array $context = []): void
    {
        $log = SmartLogger::info("{$this->auditModule()}.{$event}")
            ->event($event)
            ->module($this->auditModule())
            ->about($this)
            ->withPayload($context)
            ->activityOnly();

        if ($this->auditMaskPii()) {
            $log->withPiiMasking();
        }

        $log->save();
    }
}
