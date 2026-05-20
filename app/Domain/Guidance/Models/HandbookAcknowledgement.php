<?php

declare(strict_types=1);

namespace App\Domain\Guidance\Models;

use App\Domain\Core\Models\BaseModel;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks user acknowledgement of handbook versions.
 *
 * S1 - Secure: Provides audit trail for compliance requirements.
 */
#[Fillable(['user_id', 'handbook_id', 'acknowledged_at', 'ip_address'])]
class HandbookAcknowledgement extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handbook(): BelongsTo
    {
        return $this->belongsTo(Handbook::class);
    }
}
