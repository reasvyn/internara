<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Models;

use App\Domain\Core\Concerns\HasUuid;
use Database\Factories\DepartmentCompetencyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot model for department-competency relationship.
 *
 * S2 - Sustain: Flexible competency assignment to departments.
 */
#[Fillable(['department_id', 'competency_id', 'is_active'])]
class DepartmentCompetency extends Model
{
    use HasFactory, HasUuid;

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the department.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the competency.
     */
    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    /**
     * Create a new factory instance.
     */
    protected static function newFactory(): DepartmentCompetencyFactory
    {
        return DepartmentCompetencyFactory::new();
    }
}
