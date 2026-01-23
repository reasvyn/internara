<?php

declare(strict_types=1);

namespace Modules\Internship\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Shared\Models\Concerns\HasUuid;

class InternshipRequirement extends Model
{
    use HasFactory;
    use HasUuid;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'type',
        'is_mandatory',
        'is_active',
        'academic_year',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => \Modules\Internship\Enums\RequirementType::class,
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the submissions for this requirement.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(RequirementSubmission::class, 'requirement_id');
    }
}
