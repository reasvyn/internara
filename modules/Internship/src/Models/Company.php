<?php

declare(strict_types=1);

namespace Modules\Internship\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Internship\Database\Factories\CompanyFactory;
use Modules\Shared\Models\Concerns\HasUuid;

/**
 * Class Company
 *
 * Represents an industry partner (master data).
 */
class Company extends Model
{
    use HasFactory;
    use HasUuid;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'internship_companies';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'address',
        'business_field',
        'phone',
        'fax',
        'email',
        'leader_name',
    ];

    /**
     * Get the placements for this company.
     */
    public function placements(): HasMany
    {
        return $this->hasMany(InternshipPlacement::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }
}
