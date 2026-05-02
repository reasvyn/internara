<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AssignmentStatus;
use App\Models\Concerns\HasUuid;
use Database\Factories\AssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Assignment instance linked to an internship program.
 *
 * S1 - Secure: Validates submission requirements.
 * S2 - Sustain: Rich model with business rules.
 */
class Assignment extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'assignment_type_id',
        'internship_id',
        'academic_year',
        'title',
        'group',
        'description',
        'is_mandatory',
        'due_date',
        'config',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'due_date' => 'datetime',
        'config' => 'array',
        'status' => AssignmentStatus::class,
    ];

    /**
     * Get the assignment type.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(AssignmentType::class, 'assignment_type_id');
    }

    /**
     * Get the internship program.
     */
    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class, 'internship_id');
    }

    /**
     * Get submissions for this assignment.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Check if assignment is mandatory.
     */
    public function isMandatory(): bool
    {
        return $this->is_mandatory === true;
    }

    /**
     * Check if assignment is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_date && now()->greaterThan($this->due_date);
    }

    /**
     * Create a new factory instance.
     */
    protected static function newFactory(): AssignmentFactory
    {
        return AssignmentFactory::new();
    }
}
