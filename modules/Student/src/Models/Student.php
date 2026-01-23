<?php

declare(strict_types=1);

namespace Modules\Student\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Profile\Models\Concerns\HasProfileMorphRelation;
use Modules\Shared\Models\Concerns\HasUuid;

/**
 * Class Student
 *
 * Represents specific data for a Student.
 */
class Student extends Model
{
    use HasFactory;
    use HasProfileMorphRelation;
    use HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['nisn'];
}
