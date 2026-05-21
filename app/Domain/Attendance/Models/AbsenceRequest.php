<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Models;

use App\Domain\Attendance\Entities\AbsenceRequestStatus as AbsenceRequestStatusEntity;
use App\Domain\Attendance\Enums\AbsenceReasonType;
use App\Domain\Attendance\Enums\AbsenceRequestStatus;
use App\Domain\Core\Models\BaseModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'registration_id', 'start_date', 'end_date', 'reason_type', 'reason_description', 'attachment_path', 'status', 'processed_by', 'processed_at', 'admin_notes'])]
class AbsenceRequest extends BaseModel
{
    use HasFactory;

    protected $attributes = [
        'status' => AbsenceRequestStatus::Pending->value,
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => AbsenceRequestStatus::class,
            'reason_type' => AbsenceReasonType::class,
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function asAbsenceRequestStatus(): AbsenceRequestStatusEntity
    {
        return AbsenceRequestStatusEntity::fromModel($this);
    }
}
