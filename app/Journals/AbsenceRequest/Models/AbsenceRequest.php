<?php

declare(strict_types=1);

namespace App\Journals\AbsenceRequest\Models;

use App\Core\Models\BaseModel;
use App\Enrollment\Registration\Models\Registration;
use App\Journals\AbsenceRequest\Enums\AbsenceReasonType;
use App\Journals\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\User\Models\User;
use Database\Factories\AbsenceRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[
    Fillable([
        'user_id',
        'registration_id',
        'date',
        'absence_type',
        'absence_reason',
        'absence_attachment',
        'absence_status',
        'absence_processed_by',
        'absence_processed_at',
        'absence_admin_notes',
    ]),
]
class AbsenceRequest extends BaseModel
{
    use HasFactory;

    protected $table = 'attendances';

    protected static function newFactory(): AbsenceRequestFactory
    {
        return AbsenceRequestFactory::new();
    }

    protected $attributes = [
        'absence_status' => AbsenceRequestStatus::PENDING->value,
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'absence_type' => AbsenceReasonType::class,
            'absence_status' => AbsenceRequestStatus::class,
            'absence_processed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('absence', function (Builder $query) {
            $query->whereNotNull('absence_type');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'absence_processed_by');
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }
}
