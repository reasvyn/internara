<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\AbsenceRequest\AbsenceRequestStatus as AbsenceRequestStatusEntity;
use App\Enums\Attendance\AbsenceReasonType;
use App\Enums\Attendance\AbsenceRequestStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'registration_id', 'start_date', 'end_date', 'reason_type', 'reason_description', 'attachment_path', 'status', 'processed_by', 'processed_at', 'admin_notes'])]
class AbsenceRequest extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => AbsenceRequestStatus::class,
        'reason_type' => AbsenceReasonType::class,
        'processed_at' => 'datetime',
    ];

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

    public function entity(): AbsenceRequestStatusEntity
    {
        return AbsenceRequestStatusEntity::fromModel($this);
    }

    public function isPending(): bool
    {
        return $this->entity()->isPending();
    }

    public function isProcessed(): bool
    {
        return $this->entity()->isProcessed();
    }
}
