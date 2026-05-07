<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Internship\RegistrationState;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\ModelStatus\HasStatuses;

/**
 * Represents a student's registration for a specific internship program.
 */
#[Fillable(['student_id', 'internship_id', 'placement_id', 'teacher_id', 'mentor_id', 'academic_year', 'start_date', 'end_date', 'proposed_company_name', 'proposed_company_address', 'status'])]
class Registration extends BaseModel
{
    protected $table = 'internship_registrations';

    use HasFactory, HasStatuses;

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function entity(): RegistrationState
    {
        return RegistrationState::fromModel($this);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class, 'placement_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(LogbookEntry::class, 'registration_id');
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class, 'registration_id');
    }

    public function supervisionLogs(): HasMany
    {
        return $this->hasMany(SupervisionLog::class, 'registration_id');
    }

    public function monitoringVisits(): HasMany
    {
        return $this->hasMany(MonitoringVisit::class, 'registration_id');
    }

    public function requirementSubmissions(): HasMany
    {
        return $this->hasMany(RequirementSubmission::class, 'registration_id');
    }

    public function isActive(): bool
    {
        return $this->entity()->isActive();
    }

    public function isPending(): bool
    {
        return $this->entity()->isPending();
    }

    public function isCurrentlyOngoing(): bool
    {
        return $this->entity()->isCurrentlyOngoing();
    }

    public function hasEnded(): bool
    {
        return $this->entity()->hasEnded();
    }

    public function canBeApproved(): bool
    {
        return $this->entity()->canBeApproved();
    }

    public function daysRemaining(): int
    {
        return $this->entity()->daysRemaining();
    }

    public function totalDuration(): int
    {
        return $this->entity()->totalDuration();
    }
}
