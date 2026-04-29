<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasUuid;

    protected $fillable = [
        'user_id',
        'subject_id',
        'subject_type',
        'action',
        'payload',
        'ip_address',
        'user_agent',
        'module',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
