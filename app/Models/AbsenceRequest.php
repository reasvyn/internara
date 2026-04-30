<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AbsenceReasonType;
use App\Enums\AbsenceRequestStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsenceRequest extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
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
    ];

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
        return $this->belongsTo(InternshipRegistration::class, 'registration_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isPending(): bool
    {
        return $this->status === AbsenceRequestStatus::PENDING;
    }

    public function isProcessed(): bool
    {
        return $this->status?->isProcessed() ?? false;
    }
}
