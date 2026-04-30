<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks user acknowledgement of handbook versions.
 *
 * S1 - Secure: Provides audit trail for compliance requirements.
 */
class HandbookAcknowledgement extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'user_id',
        'handbook_id',
        'acknowledged_at',
        'ip_address',
    ];

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
