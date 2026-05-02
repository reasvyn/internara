<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Database\Factories\CompetencyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Competency definition for assessment criteria.
 *
 * S2 - Sustain: Reusable competency templates.
 */
class Competency extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'department_id',
        'name',
        'code',
        'description',
        'max_score',
        'weight',
    ];

    protected $casts = [
        'max_score' => 'float',
        'weight' => 'float',
    ];

    /**
     * Get the department this competency belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Create a new factory instance.
     */
    protected static function newFactory(): CompetencyFactory
    {
        return CompetencyFactory::new();
    }
}
