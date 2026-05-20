<?php

declare(strict_types=1);

namespace App\Domain\Assignment\Models;

use App\Domain\Assignment\Entities\AssignmentRules;
use App\Domain\Assignment\Enums\AssignmentStatus;
use App\Domain\Core\Models\BaseModel;
use Database\Factories\AssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
#[Fillable(['assignment_type_id', 'internship_id', 'academic_year', 'title', 'group', 'description', 'is_mandatory', 'due_date', 'config', 'status', 'created_by', 'document_id'])]
class Assignment extends BaseModel
{
    use HasFactory;

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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function asAssignmentRules(): AssignmentRules
    {
        return AssignmentRules::fromModel($this);
    }

    /**
     * Create a new factory instance.
     */
    protected static function newFactory(): AssignmentFactory
    {
        return AssignmentFactory::new();
    }
}
