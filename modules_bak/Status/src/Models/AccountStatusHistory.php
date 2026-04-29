<?php

declare(strict_types=1);

namespace Modules\Status\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Shared\Models\Concerns\HasUuid;
use Modules\User\Models\User;

/**
 * AccountStatusHistory Model
 *
 * Audit trail for all account status changes.
 * Every transition is logged with full context for compliance and debugging.
 */
class AccountStatusHistory extends Model
{
    use HasUuid;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'account_status_history';

    protected $timestamps = false;

    protected $fillable = [
        'user_id',
        'old_status',
        'new_status',
        'reason',
        'triggered_by_user_id',
        'triggered_by_role',
        'ip_address',
        'user_agent',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'json',
        'created_at' => 'datetime',
    ];

    /**
     * The user whose status changed.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The admin who triggered the change.
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }
}
