<?php

declare(strict_types=1);

namespace App\Domain\User\Models;

use App\Domain\Core\Models\BaseModel;
use App\Domain\School\Models\Department;
use App\Domain\School\Models\School;
use App\Domain\User\Enums\BloodType;
use App\Domain\User\Enums\EmploymentStatus;
use App\Domain\User\Enums\Gender;
use App\Domain\User\Enums\StructuralPosition;
use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    'national_id_number',
    'student_id_number',
    'school_id',
    'department_id',
    'employment_status',
    'nip',
    'nuptk',
    'competence_field',
    'position',
])]

class Profile extends BaseModel
{
    use HasFactory;

    protected static function newFactory(): ProfileFactory
    {
        return ProfileFactory::new();
    }

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'blood_type' => BloodType::class,
            'employment_status' => EmploymentStatus::class,
            'position' => StructuralPosition::class,
            'dob' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
