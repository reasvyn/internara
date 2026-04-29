<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupervisionLog extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'registration_id',
        'supervisor_id',
        'type',
        'date',
        'topic',
        'notes',
        'status',
        'is_verified',
        'verified_at',
        'attachment_path',
    ];

    protected $casts = [
        'date' => 'date',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(InternshipRegistration::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
