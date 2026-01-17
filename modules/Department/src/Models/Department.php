<?php

declare(strict_types=1);

namespace Modules\Department\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Department\Database\Factories\DepartmentFactory;
use Modules\School\Models\Concerns\HasSchoolRelation;
use Modules\Shared\Models\Concerns\HasUuid;

class Department extends Model
{
    use HasFactory;
    use HasSchoolRelation;
    use HasUuid;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'description', 'school_id'];

    protected static function newFactory(): DepartmentFactory
    {
        return DepartmentFactory::new();
    }
}
