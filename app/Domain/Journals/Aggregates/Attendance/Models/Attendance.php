<?php

declare(strict_types=1);

namespace App\Domain\Journals\Aggregates\Attendance\Models;

use App\Domain\Core\Models\BaseModel;
use App\Domain\Journals\Aggregates\Attendance\Entities\AttendanceStatus as AttendanceStatusEntity;
use App\Domain\Journals\Aggregates\Attendance\Enums\AttendanceStatus;
use App\Domain\User\Models\User;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'registration_id', 'date', 'clock_in', 'clock_out', 'clock_in_ip', 'clock_out_ip', 'clock_in_latitude', 'clock_in_longitude', 'clock_out_latitude', 'clock_out_longitude', 'status', 'is_verified', 'verified_by', 'verified_at', 'notes'])]
class Attendance extends BaseModel
{
    use HasFactory;

    protected static function newFactory(): AttendanceFactory
    {
        return AttendanceFactory::new();
    }

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
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function asAttendanceStatus(): AttendanceStatusEntity
    {
        return AttendanceStatusEntity::fromModel($this);
    }
}
