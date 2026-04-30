<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\ModelStatus\HasStatuses;

/**
 * Represents a student's registration for a specific internship program.
 */
class InternshipRegistration extends Model
{
    use HasFactory, HasUuid, HasStatuses;

    protected $fillable = [
        'student_id',
        'internship_id',
        'placement_id',
        'teacher_id',
        'mentor_id',
        'academic_year',
        'start_date',
        'end_date',
        'proposed_company_name',
        'proposed_company_address',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

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
        return $this->belongsTo(InternshipPlacement::class, 'placement_id');
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
        return $this->hasMany(JournalEntry::class, 'registration_id');
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

    /**
     * Check if the registration is currently active.
     */
    public function isActive(): bool
    {
        return $this->latestStatus()?->name === 'active';
    }

    /**
     * Check if the registration is pending approval.
     */
    public function isPending(): bool
    {
        return $this->latestStatus()?->name === 'pending';
    }

    /**
     * Check if the internship period is currently ongoing.
     */
    public function isCurrentlyOngoing(): bool
    {
        if (!$this->start_date || !$this->end_date) {
            return false;
        }

        $now = Carbon::today();

        return $now->between($this->start_date, $this->end_date, true);
    }

    /**
     * Check if the internship period has ended.
     */
    public function hasEnded(): bool
    {
        if (!$this->end_date) {
            return false;
        }

        return Carbon::today()->isAfter($this->end_date);
    }

    /**
     * Check if the registration can be approved.
     */
    public function canBeApproved(): bool
    {
        return $this->isPending() && $this->placement_id !== null;
    }

    /**
     * Get the number of days remaining in the internship.
     */
    public function daysRemaining(): int
    {
        if (!$this->end_date) {
            return 0;
        }

        $remaining = Carbon::today()->diffInDays($this->end_date, false);

        return max(0, $remaining);
    }

    /**
     * Get the total duration of the internship in days.
     */
    public function totalDuration(): int
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        return $this->start_date->diffInDays($this->end_date);
    }
}
