<?php

declare(strict_types=1);

namespace Modules\Assignment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Log\Concerns\HandlesAuditLog;
use Modules\Shared\Models\Concerns\HasUuid;

class Assignment extends Model
{
    use HandlesAuditLog, HasFactory, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_mandatory' => 'boolean',
        'due_date' => 'datetime',
        'config' => 'array',
    ];

    /**
     * Get the type of this assignment.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(AssignmentType::class, 'assignment_type_id');
    }

    /**
     * Get the submissions for this assignment.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Modules\Assignment\Database\Factories\AssignmentFactory
    {
        return \Modules\Assignment\Database\Factories\AssignmentFactory::new();
    }
}
