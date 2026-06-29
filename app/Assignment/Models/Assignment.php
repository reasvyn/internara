<?php

declare(strict_types=1);

namespace App\Assignment\Models;

use App\Assignment\Entities\AssignmentRules;
use App\Assignment\Enums\AssignmentStatus;
use App\Assignment\Submission\Models\Submission;
use App\Core\Models\BaseModel;
use App\Document\Models\Document;
use App\Program\Internship\Models\Internship;
use App\User\Models\User;
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
#[
    Fillable([
        'internship_id',
        'document_id',
        'assignment_type',
        'title',
        'description',
        'is_mandatory',
        'due_date',
        'status',
        'created_by',
    ]),
]
class Assignment extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'due_date' => 'datetime',
        'is_mandatory' => 'boolean',
        'status' => AssignmentStatus::class,
    ];

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
