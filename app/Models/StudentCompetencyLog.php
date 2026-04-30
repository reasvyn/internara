<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Student competency assessment log.
 *
 * S2 - Sustain: Tracks competency progress over time.
 */
class StudentCompetencyLog extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'registration_id',
        'competency_id',
        'evaluator_id',
        'score',
        'notes',
    ];

    protected $casts = [
        'score' => 'float',
    ];

    /**
     * Get the internship registration.
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(InternshipRegistration::class, 'registration_id');
    }

    /**
     * Get the competency.
     */
    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    /**
     * Get the evaluator.
     */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    /**
     * Create a new factory instance.
     */
    protected static function newFactory(): \Database\Factories\StudentCompetencyLogFactory
    {
        return \Database\Factories\StudentCompetencyLogFactory::new();
    }
}
