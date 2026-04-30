<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BloodType;
use App\Enums\Gender;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Extended information for a User.
 */
class Profile extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'user_id',
        'phone',
        'address',
        'gender',
        'blood_type',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_address',
        'bio',
        'national_identifier',
        'registration_number',
        'department_id',
    ];

    protected $casts = [
        'gender' => Gender::class,
        'blood_type' => BloodType::class,
    ];

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
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
