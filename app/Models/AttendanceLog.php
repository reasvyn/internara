<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'user_id',
        'registration_id',
        'date',
        'clock_in',
        'clock_out',
        'clock_in_ip',
        'clock_out_ip',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_out_latitude',
        'clock_out_longitude',
        'status',
        'is_verified',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime:H:i',
        'clock_out' => 'datetime:H:i',
        'status' => AttendanceStatus::class,
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(InternshipRegistration::class, 'registration_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function hasClockOut(): bool
    {
        return $this->clock_out !== null;
    }

    public function isExcused(): bool
    {
        return $this->status?->isExcused() ?? false;
    }
}
