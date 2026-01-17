<?php

declare(strict_types=1);

namespace Modules\Internship\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Internship\Database\Factories\InternshipFactory;
use Modules\School\Models\Concerns\HasSchoolRelation;

class Internship extends Model
{
    use HasFactory;
    use HasSchoolRelation;
    use HasUuids;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'description',
        'year',
        'semester',
        'date_start',
        'date_finish',
        'school_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'year' => 'integer', // Changed from year to integer for better compatibility
        'semester' => 'string',
        'date_start' => 'date',
        'date_finish' => 'date',
    ];

    /**
     * Get the placements available for this internship program.
     */
    public function placements(): HasMany
    {
        return $this->hasMany(InternshipPlacement::class);
    }

    /**
     * Get all student registrations for this internship program.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(InternshipRegistration::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): InternshipFactory
    {
        return InternshipFactory::new();
    }
}
