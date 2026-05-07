<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\User\BloodType;
use App\Enums\User\Gender;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Extended information for a User.
 *
 * Stores optional demographic, contact, and organizational details
 * associated with a user account.
 */
#[Fillable([
    'user_id',
    'phone',
    'address',
    'gender',
    'blood_type',
    'pob',
    'dob',
    'emergency_contact_name',
    'emergency_contact_phone',
    'emergency_contact_address',
    'bio',
    'national_identifier',
    'registration_number',
    'school_id',
    'department_id',
])]
class Profile extends BaseModel
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'blood_type' => BloodType::class,
            'dob' => 'date',
        ];
    }

    /**
     * Get the user that owns the profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the school the profile belongs to.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the department the profile belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
