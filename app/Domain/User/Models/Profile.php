<?php

declare(strict_types=1);

namespace App\Domain\User\Models;

use App\Domain\School\Models\Department;
use App\Domain\User\Enums\BloodType;
use App\Domain\User\Enums\Gender;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Extended information for a User.
 *
 * Stores optional demographic, contact, and organizational details
 * associated with a user account.
 */
#[Fillable(['user_id', 'phone', 'address', 'gender', 'blood_type', 'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_address', 'bio', 'national_identifier', 'registration_number', 'department_id'])]
class Profile extends Model
{
    use HasFactory, HasUuid;

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
     * Get the department the profile belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
