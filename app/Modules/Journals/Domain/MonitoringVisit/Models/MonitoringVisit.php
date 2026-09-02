<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\MonitoringVisit\Models;

use App\Modules\Core\Models\BaseModel;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journals\Domain\MonitoringVisit\Entities\VisitState;
use App\Modules\Journals\Domain\MonitoringVisit\Enums\VisitMethod;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Database\Factories\MonitoringVisitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property Carbon $visit_date
 * @property int $duration_minutes
 * @property bool $is_verified
 * @property Carbon $verified_at
 * @property VisitMethod $method
 * @property string $registration_id
 * @property string $teacher_id
 * @property string $location
 * @property string $notes
 * @property string $student_condition
 * @property string $company_feedback
 * @property string $follow_up_actions
 * @property string $verified_by
 * @property-read Registration|null $registration
 * @property-read User|null $teacher
 * @property-read User|null $verifier
 */
#[
    Fillable([
        'registration_id',
        'teacher_id',
        'visit_date',
        'method',
        'location',
        'duration_minutes',
        'notes',
        'student_condition',
        'company_feedback',
        'follow_up_actions',
        'is_verified',
        'verified_by',
        'verified_at',
    ]),
]
class MonitoringVisit extends BaseModel
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'method' => VisitMethod::class,
            'duration_minutes' => 'integer',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function asVisitState(): VisitState
    {
        return VisitState::fromModel($this);
    }

    protected static function newFactory(): MonitoringVisitFactory
    {
        return MonitoringVisitFactory::new();
    }
}
