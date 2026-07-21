<?php

declare(strict_types=1);

namespace App\Journals\MonitoringVisit\Models;

use App\Core\Models\BaseModel;
use App\Enrollment\Registration\Models\Registration;
use App\Journals\MonitoringVisit\Entities\VisitState;
use App\Journals\MonitoringVisit\Enums\VisitMethod;
use App\User\Models\User;
use Database\Factories\MonitoringVisitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
