<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Defines what a student needs to submit for an internship program.
 */
class InternshipRequirement extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'description',
        'type', // document, skill, text
        'is_mandatory',
        'is_active',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function submissions(): HasMany
    {
        return $this->hasMany(RequirementSubmission::class, 'requirement_id');
    }
}
