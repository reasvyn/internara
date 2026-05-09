<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Notification\NotificationStatus;
use Database\Factories\NotificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * In-app notification for users.
 *
 * S1 - Secure: User-specific notifications.
 * S2 - Sustain: Rich model with read tracking.
 */
#[Fillable(['user_id', 'type', 'title', 'message', 'data', 'link', 'is_read', 'read_at'])]
class Notification extends BaseModel
{
    use HasFactory;

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

    public function asNotificationStatus(): NotificationStatus
    {
        return NotificationStatus::fromModel($this);
    }

    /**
     * Create a new factory instance.
     */
    protected static function newFactory(): NotificationFactory
    {
        return NotificationFactory::new();
    }
}
