<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot model for department-competency relationship.
 *
 * S2 - Sustain: Flexible competency assignment to departments.
 */
class DepartmentCompetency extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'department_id',
        'competency_id',
        'is_active',
    ];

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
    protected static function newFactory(): \Database\Factories\DepartmentCompetencyFactory
    {
        return \Database\Factories\DepartmentCompetencyFactory::new();
    }
}
