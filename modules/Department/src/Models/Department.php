<?php

declare(strict_types=1);

namespace Modules\Department\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Department\Database\Factories\DepartmentFactory;
use Modules\School\Models\Concerns\HasSchoolRelation;
use Modules\Shared\Models\Concerns\HasUuid;

/**
 * Class Department
 * 
 * Represents an academic department within a school.
 */
class Department extends Model
{
    use HasFactory;
    use HasSchoolRelation;
    use HasUuid;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'description', 'school_id'];

    /**
     * Optimized Defaults: Eager load school to prevent N+1 queries by default.
     */
    protected $with = ['school'];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function newFactory(): DepartmentFactory
    {
        return DepartmentFactory::new();
    }
}
