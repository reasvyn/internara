<?php

declare(strict_types=1);

namespace App\Domain\Shared\Support;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Provides ownership relationship and query scoping for Eloquent models.
 *
 * Usage:
 *   class Post extends BaseModel
 *   {
 *       use HasOwner;
 *
 *       protected static function ownerForeignKey(): string
 *       {
 *           return 'created_by';
 *       }
 *   }
 *
 * Defaults to `user_id` foreign key. Override `ownerForeignKey()` for custom column.
 */
trait HasOwner
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, static::ownerForeignKey());
    }

    protected static function ownerForeignKey(): string
    {
        return 'user_id';
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->{static::ownerForeignKey()} === $user->id;
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where(static::ownerForeignKey(), $user->id);
    }
}
