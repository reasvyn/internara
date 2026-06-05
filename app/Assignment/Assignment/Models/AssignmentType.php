<?php

declare(strict_types=1);

namespace App\Assignment\Assignment\Models;

use App\Core\Models\BaseModel;
use Database\Factories\AssignmentTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'group', 'description'])]
class AssignmentType extends BaseModel
{
    use HasFactory;

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'assignment_type_id');
    }

    protected static function newFactory(): AssignmentTypeFactory
    {
        return AssignmentTypeFactory::new();
    }
}
