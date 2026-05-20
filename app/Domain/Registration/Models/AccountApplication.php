<?php

declare(strict_types=1);

namespace App\Domain\Registration\Models;

use App\Domain\Core\Models\BaseModel;
use App\Domain\Internship\Models\Internship;
use App\Domain\Placement\Models\Placement;
use App\Domain\School\Models\Department;
use App\Domain\School\Models\School;
use App\Domain\User\Models\User;
use Database\Factories\AccountApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'name',
    'email',
    'phone',
    'address',
    'national_identifier',
    'registration_number',
    'school_id',
    'department_id',
    'class_name',
    'entry_year',
    'internship_id',
    'placement_id',
    'academic_year',
    'proposed_company_name',
    'proposed_company_address',
    'status',
    'processed_by',
    'processed_at',
    'rejection_reason',
])]
class AccountApplication extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'entry_year' => 'integer',
        'processed_at' => 'datetime',
    ];

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class, 'placement_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    protected static function newFactory(): AccountApplicationFactory
    {
        return AccountApplicationFactory::new();
    }
}
