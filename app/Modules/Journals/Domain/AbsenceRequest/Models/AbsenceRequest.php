<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\AbsenceRequest\Models;

use App\Modules\Core\Models\BaseModel;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journals\Domain\AbsenceRequest\Enums\AbsenceReasonType;
use App\Modules\Journals\Domain\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Database\Factories\AbsenceRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property Carbon $date
 * @property Carbon $absence_processed_at
 * @property AbsenceReasonType $absence_type
 * @property AbsenceRequestStatus $absence_status
 * @property string $user_id
 * @property string $registration_id
 * @property string $absence_reason
 * @property string $absence_attachment
 * @property string $absence_processed_by
 * @property string $absence_admin_notes
 * @property-read User|null $user
 * @property-read User|null $processor
 * @property-read Registration|null $registration
 */
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
