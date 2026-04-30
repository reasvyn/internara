<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * In-app notification for users.
 *
 * S1 - Secure: User-specific notifications.
 * S2 - Sustain: Rich model with read tracking.
 */
class Notification extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'link',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Get the user this notification belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(): void
    {
        if (! $this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Check if notification is unread.
     */
    public function isUnread(): bool
    {
        return ! $this->is_read;
    }

    /**
     * Create a new factory instance.
     */
    protected static function newFactory(): \Database\Factories\NotificationFactory
    {
        return \Database\Factories\NotificationFactory::new();
    }
}
