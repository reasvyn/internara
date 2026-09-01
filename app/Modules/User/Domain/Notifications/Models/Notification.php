<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Notifications\Models;

use App\Modules\Core\Models\BaseModel;
use Database\Factories\NotificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * @property string $id
 * @property array $data
 * @property bool $is_read
 * @property \Carbon\Carbon $read_at
 * @property string $user_id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property string $link
 * @property-read \App\Modules\User\Domain\Notifications\Models\User|null $user
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

    protected static function newFactory(): NotificationFactory
    {
        return NotificationFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
