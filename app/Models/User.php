<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccountStatus;
use App\Models\Concerns\HasUuid;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\ModelStatus\HasStatuses;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles, HasUuid, HasStatuses;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'setup_required',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'setup_required' => 'boolean',
        ];
    }

    /**
     * Get the profile associated with the user.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * Get all internship registrations for this user (as student, teacher, or mentor).
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(InternshipRegistration::class, 'student_id');
    }

    /**
     * Get registrations where this user is the assigned teacher.
     */
    public function teachingRegistrations(): HasMany
    {
        return $this->hasMany(InternshipRegistration::class, 'teacher_id');
    }

    /**
     * Get registrations where this user is the assigned mentor.
     */
    public function mentoringRegistrations(): HasMany
    {
        return $this->hasMany(InternshipRegistration::class, 'mentor_id');
    }

    /**
     * Check if the user is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->latestStatus()?->name === AccountStatus::SUSPENDED->value;
    }

    /**
     * Check if the user is archived.
     */
    public function isArchived(): bool
    {
        return $this->latestStatus()?->name === AccountStatus::ARCHIVED->value;
    }

    /**
     * Check if the user is inactive.
     */
    public function isInactive(): bool
    {
        return $this->latestStatus()?->name === AccountStatus::INACTIVE->value;
    }
}
