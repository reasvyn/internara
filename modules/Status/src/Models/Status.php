<?php

declare(strict_types=1);

namespace Modules\Status\Models;

use Modules\Log\Concerns\InteractsWithActivityLog;
use Modules\Shared\Models\Concerns\HasUuid;
use Spatie\ModelStatus\Status as SpatieStatus;

/**
 * Class Status
 *
 * Repositories for all entity state transitions.
 * Supports UUID identity and detailed activity logging.
 */
class Status extends SpatieStatus
{
    use HasUuid;
    use InteractsWithActivityLog;

    /**
     * The name of the activity log for this model.
     */
    protected string $activityLogName = 'status';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'reason', 'model_id', 'model_type', 'expires_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Check if the status has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Scope a query to only include expired statuses.
     */
    public function scopeExpired($query): void
    {
        $query->whereNotNull('expires_at')->where('expires_at', '<=', now());
    }

    /**
     * Scope a query to only include non-expired statuses.
     */
    public function scopeNotExpired($query): void
    {
        $query->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }
}
