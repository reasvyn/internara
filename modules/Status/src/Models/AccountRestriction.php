<?php

declare(strict_types=1);

namespace Modules\Status\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Models\Concerns\HasUuid;
use Modules\User\Models\User;

/**
 * AccountRestriction Model
 *
 * Represents fine-grained restrictions applied to an account.
 * Used to limit access to specific modules, features, or actions
 * without fully suspending the account.
 */
class AccountRestriction extends Model
{
    use HasUuid;
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'account_restrictions';

    protected $fillable = [
        'user_id',
        'restriction_type',
        'restriction_key',
        'restriction_value',
        'reason',
        'applied_by_user_id',
        'applied_at',
        'expires_at',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'applied_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'json',
    ];

    /**
     * The user being restricted.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The admin who applied the restriction.
     */
    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by_user_id');
    }

    /**
     * Scope: only active restrictions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Check if this restriction has expired.
     */
    public function hasExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Auto-lift restriction if expired.
     */
    public function autoLiftIfExpired(): bool
    {
        if (!$this->hasExpired()) {
            return false;
        }

        $this->update(['is_active' => false]);

        return true;
    }
}
