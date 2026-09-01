<?php

declare(strict_types=1);

namespace App\Modules\Academics\Domain\Department\Models;

use App\Modules\Academics\Domain\Department\Entities\DepartmentState;
use App\Modules\Core\Models\BaseModel;
use App\Modules\User\Domain\Profile\Models\Profile;
use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;


/**
 * @property string $id
 * @property string $name
 * @property string $description
 * @property-read \Illuminate\Database\Eloquent\Collection<int,\App\Modules\User\Domain\Profile\Models\Profile> $profiles
 */

#[Fillable(['name', 'description'])]
class Department extends BaseModel
{
    use HasFactory;

    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class);
    }

    public function asDepartmentState(): DepartmentState
    {
        return DepartmentState::fromModel($this);
    }

    protected static function newFactory(): DepartmentFactory
    {
        return DepartmentFactory::new();
    }
}
