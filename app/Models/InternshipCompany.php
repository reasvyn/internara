<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a company/industry partner.
 */
class InternshipCompany extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'website',
        'description',
        'industry_sector',
    ];

    public function placements(): HasMany
    {
        return $this->hasMany(InternshipPlacement::class, 'company_id');
    }
}
