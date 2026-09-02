<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\Attendance\Models;

use App\Modules\Core\Models\BaseModel;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journals\Domain\Attendance\Entities\AttendanceState as AttendanceStateEntity;
use App\Modules\Journals\Domain\Attendance\Enums\AttendanceStatus;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property Carbon $date
 * @property string $clock_in
 * @property string $clock_out
 * @property Carbon $absence_processed_at
 * @property bool $is_verified
 * @property Carbon $verified_at
 * @property AttendanceStatus $status
 * @property string $user_id
 * @property string $registration_id
 * @property string $clock_in_ip
 * @property string $clock_out_ip
 * @property string $clock_in_latitude
 * @property string $clock_in_longitude
 * @property string $clock_out_latitude
 * @property string $clock_out_longitude
 * @property string $absence_type
 * @property string $absence_reason
 * @property string $absence_attachment
 * @property string $absence_status
 * @property string $absence_processed_by
 * @property string $absence_admin_notes
 * @property string $verified_by
 * @property string $notes
 * @property-read User|null $user
 * @property-read Registration|null $registration
 * @property-read User|null $verifier
 */
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
