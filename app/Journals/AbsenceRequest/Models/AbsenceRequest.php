<?php

declare(strict_types=1);

namespace App\Journals\AbsenceRequest\Models;

use App\Core\Models\BaseModel;
use App\Journals\AbsenceRequest\Entities\AbsenceRequestStatus as AbsenceRequestStatusEntity;
use App\Journals\AbsenceRequest\Enums\AbsenceReasonType;
use App\Journals\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\User\Models\User;
use Database\Factories\AbsenceRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[
    Fillable([
        'user_id',
        'registration_id',
        'start_date',
        'end_date',
        'reason_type',
        'reason_description',
        'attachment_path',
        'status',
        'processed_by',
        'processed_at',
        'admin_notes',
    ]),
]
class AbsenceRequest extends BaseModel
{
    use HasFactory;

    protected static function newFactory(): AbsenceRequestFactory
    {
        return AbsenceRequestFactory::new();
    }

    protected $attributes = [
        'status' => AbsenceRequestStatus::PENDING->value,
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
