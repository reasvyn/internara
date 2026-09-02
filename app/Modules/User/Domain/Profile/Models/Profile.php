<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Profile\Models;

use App\Modules\Academics\Domain\Department\Models\Department;
use App\Modules\Core\Models\BaseModel;
use App\Modules\Partners\Domain\Company\Models\Company;
use App\Modules\User\Enums\BloodType;
use App\Modules\User\Enums\Gender;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property Carbon $dob
 * @property array $emergency_contact
 * @property Gender $gender
 * @property BloodType $blood_type
 * @property string $user_id
 * @property string $phone
 * @property string $address
 * @property string $bio
 * @property string $pob
 * @property string $id_number
 * @property string $national_id_number
 * @property string $competence_field
 * @property string $employment_status
 * @property string $job_title
 * @property string $internal_notes
 * @property string $department_id
 * @property string $company_id
 * @property-read User|null $user
 * @property-read Department|null $department
 * @property-read Company|null $company
 */
#[
    Fillable([
        'user_id',
        'phone',
        'address',
        'bio',
        'gender',
        'blood_type',
        'pob',
        'dob',
        'emergency_contact',
        'id_number',
        'national_id_number',
        'competence_field',
        'employment_status',
        'job_title',
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
