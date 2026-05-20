<?php

declare(strict_types=1);

namespace App\Domain\Registration\Models;

use App\Domain\Core\Models\BaseModel;
use App\Domain\Registration\Entities\RegistrationState;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\ModelStatus\HasStatuses;

#[Fillable(['mentee_id', 'internship_id', 'placement_id', 'academic_year', 'start_date', 'end_date', 'proposed_company_name', 'proposed_company_address', 'status'])]
class Registration extends BaseModel
{
    protected $table = 'internship_registrations';

    use HasFactory, HasStatuses;

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function asRegistrationState(): RegistrationState
    {
        return RegistrationState::fromModel($this);
    }

    public function mentee(): BelongsTo
    {
        return $this->belongsTo(Mentee::class, 'mentee_id');
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class, 'placement_id');
    }

    public function mentors(): BelongsToMany
    {
        return $this->belongsToMany(Mentor::class, 'registration_mentor', 'registration_id', 'mentor_id')
            ->withPivot('role')
            ->withTimestamps();
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
}
