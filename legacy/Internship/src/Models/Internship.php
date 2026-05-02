<?php

declare(strict_types=1);

namespace Modules\Internship\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Internship\Database\Factories\InternshipFactory;
use Modules\Internship\Enums\ProgramStatus;
use Modules\Internship\Enums\Semester;
use Modules\School\Models\Concerns\HasSchoolRelation;
use Modules\Shared\Models\Concerns\HasUuid;
use Modules\Status\Concerns\HasStatuses;

class Internship extends Model
{
    use HasFactory;
    use HasSchoolRelation;
    use HasStatuses;
    use HasUuid;

    /**
     * Get the current status as a ProgramStatus Enum instance.
     */
    public function getStatus(): ?ProgramStatus
    {
        $status = $this->latestStatus();

        return $status ? ProgramStatus::tryFrom($status->name) : null;
    }

    /**
     * Get the label for the current program status.
     */
    public function getStatusLabel(): string
    {
        $status = $this->getStatus();

        return $status ? $status->label() : __('internship::ui.status.unknown');
    }

    /**
     * Get the color/variant for the current program status.
     */
    public function getStatusColor(): string
    {
        $status = $this->getStatus();

        return $status ? $status->color() : 'metadata';
    }

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'description',
        'academic_year',
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
        'academic_year' => 'string',
        'semester' => Semester::class,
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
