<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Assignment type template for defining task categories.
 *
 * S2 - Sustain: Centralized task type management.
 */
class AssignmentType extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'slug',
        'group',
        'description',
    ];

    /**
     * Get assignments of this type.
     */
    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'assignment_type_id');
    }

    /**
     * Create a new factory instance.
     */
    protected static function newFactory(): \Database\Factories\AssignmentTypeFactory
    {
        return \Database\Factories\AssignmentTypeFactory::new();
    }
}
