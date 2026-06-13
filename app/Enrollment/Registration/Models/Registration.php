<?php

declare(strict_types=1);

namespace App\Enrollment\Registration\Models;

use App\Assessment\Models\Assessment;
use App\Certification\Certificate\Models\Certificate;
use App\Core\Models\BaseModel;
use App\Enrollment\Placement\Models\Placement;
use App\Enrollment\Registration\Entities\RegistrationState;
use App\Guidance\SupervisionLog\Models\SupervisionLog;
use App\Journals\Attendance\Models\Attendance;
use App\Journals\Logbook\Models\Logbook;
use App\Program\Internship\Models\Internship;
use App\Program\InternshipGroup\Models\InternshipGroupMember;
use App\Reports\Report\Models\Report;
use App\Settings\Models\Setting;
use App\User\Models\User;
use Database\Factories\RegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public function hasStatus(string $status): bool
    {
        return $this->status === $status;
    }

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
        return RegistrationState::fromModel($this)->withPhases($this->resolvePhases());
    }

    public function currentPhaseIndex(): ?int
    {
        return $this->asRegistrationState()->currentPhaseIndex();
    }

    public function currentPhase(): ?string
    {
        return $this->asRegistrationState()->currentPhase();
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

    public function mentee(): HasOne
    {
        return $this->hasOne(InternshipGroupMember::class, 'registration_id')
            ->where('role', 'student');
    }

    public function mentors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'internship_group_members', 'registration_id', 'mentor_id')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function resolvePhases(): array
    {
        $phases = $this->internship?->phases;

        if (! empty($phases)) {
            return $phases;
        }

        $defaults = Setting::where('key', 'internship_phases')->value('value');

        return is_array($defaults) ? $defaults : [];
    }
}
