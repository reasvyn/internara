<?php

declare(strict_types=1);

namespace App\Domain\Partners\Aggregates\Company\Models;

use App\Domain\Core\Models\BaseModel;
use App\Domain\Enrollment\Models\Placement;
use App\Domain\Partners\Aggregates\Company\Entities\CompanyState;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a company/industry partner.
 */
#[Fillable(['name', 'address', 'phone', 'email', 'website', 'description', 'industry_sector'])]
class Company extends BaseModel
{
    use HasFactory;

    protected $table = 'companies';

    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class, 'company_id');
    }

    public function partnerships(): HasMany
    {
        return $this->hasMany(Partnership::class, 'company_id');
    }

    public function asCompanyState(): CompanyState
    {
        return CompanyState::fromModel($this);
    }

    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }
}
