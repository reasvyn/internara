<?php

declare(strict_types=1);

namespace App\Enrollment\Registration\Models;

use App\Assessment\Models\Assessment;
use App\Certification\Certificate\Models\Certificate;
use App\Core\Models\BaseModel;
use App\Enrollment\Registration\Entities\RegistrationState;
use App\Guidance\SupervisionLog\Models\SupervisionLog;
use App\Settings\Models\Setting;
use Carbon\Carbon;
use App\Journals\Attendance\Models\Attendance;
use App\Journals\Logbook\Models\Logbook;
use App\Program\Internship\Models\Internship;
use App\Reports\Report\Models\Report;
use App\User\Models\User;
use Database\Factories\RegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[
    Fillable([
        'student_id',
        'internship_id',
        'placement_id',
        'start_date',
        'end_date',
        'status',
        'proposed_company_details',
    ]),
]
class Registration extends BaseModel
{
    protected $table = 'registrations';

    use HasFactory;

    protected static function newFactory(): RegistrationFactory
    {
        return RegistrationFactory::new();
    }

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'proposed_company_details' => 'json',
    ];

    public function setStatus(string $status, ?string $reason = null): static
    {
        $this->update(['status' => $status]);

        return $this;
    }

    public function latestStatus()
    {
        return (object) ['name' => $this->status];
    }

    public function asRegistrationState(): RegistrationState
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

    public function logbooks(): HasMany
    {
        return $this->hasMany(Logbook::class, 'registration_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'registration_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'registration_id');
    }

    public function supervisionLogs(): HasMany
    {
        return $this->hasMany(SupervisionLog::class, 'registration_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(RegistrationDocument::class, 'registration_id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'registration_id');
    }

    public function report(): HasOne
    {
        return $this->hasOne(Report::class, 'registration_id');
    }

    /**
     * Resolve the phase definitions for this registration.
     * Uses the internship's custom phases JSON, or falls back to global defaults.
     *
     * @return array<int, array{name: string, order: int, weight: int}>
     */
    public function resolvePhases(): array
    {
        $phases = $this->internship?->phases;

        if (! empty($phases)) {
            return $phases;
        }

        $defaults = Setting::where('key', 'internship_phases')->value('value');

        return is_array($defaults) ? $defaults : [];
    }

    /**
     * Compute the current phase index based on the program date range and today's date.
     * Returns null if phases or program dates are not configured.
     */
    public function currentPhaseIndex(): ?int
    {
        $phases = $this->resolvePhases();
        $internship = $this->internship;

        if ($phases === [] || ! $internship?->start_date || ! $internship?->end_date) {
            return null;
        }

        $now = Carbon::today();
        $start = Carbon::parse($internship->start_date);
        $end = Carbon::parse($internship->end_date);
        $totalDays = $start->diffInDays($end);

        if ($totalDays <= 0) {
            return null;
        }

        $elapsedDays = $start->diffInDays($now, false);
        $elapsedPercent = ($elapsedDays / $totalDays) * 100;

        if ($elapsedPercent <= 0) {
            return 0;
        }

        $cumulative = 0;
        foreach ($phases as $index => $phase) {
            $cumulative += $phase['weight'];
            if ($elapsedPercent <= $cumulative) {
                return $index;
            }
        }

        return count($phases) - 1;
    }

    /**
     * Get the current phase name, or null if not in a phase.
     */
    public function currentPhase(): ?string
    {
        $index = $this->currentPhaseIndex();

        if ($index === null) {
            return null;
        }

        return $this->resolvePhases()[$index]['name'] ?? null;
    }
}
