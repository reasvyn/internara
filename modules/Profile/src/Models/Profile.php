<?php

declare(strict_types=1);

namespace Modules\Profile\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Department\Models\Concerns\HasDepartmentRelation;
use Modules\Profile\Database\Factories\ProfileFactory;
use Modules\Shared\Models\Concerns\HasUuid;
use Modules\Shared\Support\Casts\SafeEncrypted;
use Modules\User\Models\Concerns\HasUserRelation;

/**
 * Class Profile
 *
 * Represents extended information for a User.
 */
class Profile extends Model
{
    use HasDepartmentRelation;
    use HasFactory;
    use HasUserRelation;
    use HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'department_id',
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
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'national_identifier' => SafeEncrypted::class,
            'phone' => SafeEncrypted::class,
            'address' => SafeEncrypted::class,
            'emergency_contact_phone' => SafeEncrypted::class,
            'emergency_contact_address' => SafeEncrypted::class,
            'registration_number' => SafeEncrypted::class,
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProfileFactory
    {
        return ProfileFactory::new();
    }
}
