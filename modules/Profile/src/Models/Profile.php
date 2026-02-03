<?php

declare(strict_types=1);

namespace Modules\Profile\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Department\Models\Concerns\HasDepartmentRelation;
use Modules\Profile\Database\Factories\ProfileFactory;
use Modules\Shared\Models\Concerns\HasUuid;
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
        'user_id',
        'department_id',
        'phone',
        'address',
        'bio',
        'profileable_id',
        'profileable_type',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone' => 'encrypted',
            'address' => 'encrypted',
            'nip' => 'encrypted',
            'nisn' => 'encrypted',
        ];
    }

    /**
     * Get the associated profileable model (Student, Teacher, etc.).
     */
    public function profileable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProfileFactory
    {
        return ProfileFactory::new();
    }
}
