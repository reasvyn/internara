<?php

declare(strict_types=1);

namespace App\Journals\Attendance\Models;

use App\Core\Models\BaseModel;
use App\Enrollment\Registration\Models\Registration;
use App\Journals\Attendance\Entities\AttendanceState as AttendanceStateEntity;
use App\Journals\Attendance\Enums\AttendanceStatus;
use App\User\Models\User;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[
    Fillable([
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
        'absence_type',
        'absence_reason',
        'absence_attachment',
        'absence_status',
        'absence_processed_by',
        'absence_processed_at',
        'absence_admin_notes',
        'is_verified',
        'verified_by',
        'verified_at',
        'notes',
    ]),
]
class Attendance extends BaseModel
{
    use HasFactory;

    protected static function newFactory(): AttendanceFactory
    {
        return AttendanceFactory::new();
    }

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'string',
        'clock_out' => 'string',
        'status' => AttendanceStatus::class,
        'absence_processed_at' => 'datetime',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function asAttendanceState(): AttendanceStateEntity
    {
        return AttendanceStateEntity::fromModel($this);
    }
}
