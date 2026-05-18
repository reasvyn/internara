<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Company\CompanyState;
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

    protected $table = 'internship_companies';

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
}
