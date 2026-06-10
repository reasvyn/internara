<?php

declare(strict_types=1);

namespace App\User\Profile\Models;

use App\Academics\Department\Models\Department;
use App\Core\Models\BaseModel;
use App\Partners\Company\Models\Company;
use App\User\Enums\BloodType;
use App\User\Models\User;
use App\User\Enums\Gender;
use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[
    Fillable([
        'user_id',
        'phone',
        'address',
        'gender',
        'blood_type',
        'pob',
        'dob',
        'emergency_contact',
        'student_id_number',
        'employee_id_number',
        'mentor_type',
        'internal_notes',
        'department_id',
        'company_id',
    ]),
]
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
            'dob' => 'date',
            'emergency_contact' => 'json',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
