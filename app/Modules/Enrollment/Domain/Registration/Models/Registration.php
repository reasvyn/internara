<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Models;

use App\Modules\Assessment\Models\Assessment;
use App\Modules\Certification\Domain\Certificate\Models\Certificate;
use App\Modules\Core\Models\BaseModel;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Registration\Entities\RegistrationState;
use App\Modules\Journals\Domain\Attendance\Models\Attendance;
use App\Modules\Journals\Domain\Logbook\Models\Logbook;
use App\Modules\Journals\Domain\SupervisionLog\Models\SupervisionLog;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\Reports\Domain\StudentReport\Models\StudentReport;
use App\Modules\Settings\Models\Setting;
use App\Modules\User\Domain\Mentor\Entities\MentorEntity;
use App\Modules\User\Models\User;
use Database\Factories\RegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


/**
 * @property string $id
 * @property \Carbon\Carbon $start_date
 * @property \Carbon\Carbon $end_date
 * @property array $proposed_company_details
 * @property string $student_id
 * @property string $internship_id
 * @property string $placement_id
 * @property string $status
 * @property-read \App\Modules\User\Models\User|null $student
 * @property-read \App\Modules\Program\Domain\Internship\Models\Internship|null $internship
 * @property-read \App\Modules\Enrollment\Domain\Placement\Models\Placement|null $placement
 * @property-read \Illuminate\Database\Eloquent\Collection<int,\App\Modules\Journals\Domain\Logbook\Models\Logbook> $logbooks
 * @property-read \Illuminate\Database\Eloquent\Collection<int,\App\Modules\Journals\Domain\Attendance\Models\Attendance> $attendances
 * @property-read \Illuminate\Database\Eloquent\Collection<int,\App\Modules\Certification\Domain\Certificate\Models\Certificate> $certificates
 * @property-read \Illuminate\Database\Eloquent\Collection<int,\App\Modules\Journals\Domain\SupervisionLog\Models\SupervisionLog> $supervisionLogs
 * @property-read \Illuminate\Database\Eloquent\Collection<int,\App\Modules\Enrollment\Domain\Registration\Models\RegistrationDocument> $documents
 * @property-read \Illuminate\Database\Eloquent\Collection<int,\App\Modules\Assessment\Models\Assessment> $assessments
 * @property-read \App\Modules\Reports\Domain\StudentReport\Models\StudentReport|null $report
 * @property-read \App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember|null $mentee
 * @property-read \Illuminate\Database\Eloquent\Collection<int,\App\Modules\User\Models\User> $mentors
 */

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

    public function hasStatus(string $status): bool
    {
        return $this->status === $status;
    }

    public function scopeCurrentStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function asRegistrationState(): RegistrationState
    {
        return RegistrationState::fromModel($this)->withPhases($this->resolvePhases());
    }

    public function asMentorEntity(): MentorEntity
    {
        return MentorEntity::fromModel($this);
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
        return $this->hasOne(StudentReport::class, 'registration_id');
    }

    public function mentee(): HasOne
    {
        return $this->hasOne(InternshipGroupMember::class, 'registration_id');
    }

    public function mentors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'internship_group_members', 'registration_id', 'user_id')
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    public function scopeWhereHasMentor(Builder $query, User $user): Builder
    {
        return $query->whereHas('mentors', fn ($q) => $q->where('user_id', $user->id));
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
