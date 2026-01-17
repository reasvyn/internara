<?php

declare(strict_types=1);

namespace Modules\Internship\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Internship\Database\Factories\InternshipRegistrationFactory;
use Modules\Shared\Models\Concerns\HasStatus;
use Modules\Shared\Models\Concerns\HasUuid;
use Modules\User\Models\Concerns\HasUserRelation;

class InternshipRegistration extends Model
{
    use HasFactory;
    use HasStatus;
    use HasUserRelation; // For student relation
    use HasUuid;

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
     *
     * @var array<int, string>
     */
    protected $fillable = ['internship_id', 'placement_id', 'student_id', 'teacher_id', 'mentor_id'];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): InternshipRegistrationFactory
    {
        return InternshipRegistrationFactory::new();
    }

    /**
     * Get the student (user) associated with the registration.
     */
    public function user(): BelongsTo
    {
        return app(\Modules\User\Services\Contracts\UserService::class)->defineBelongsTo(
            $this,
            'student_id',
        );
    }

    /**
     * Get the teacher (user) associated with the registration.
     */
    public function teacher(): BelongsTo
    {
        return app(\Modules\User\Services\Contracts\UserService::class)->defineBelongsTo(
            $this,
            'teacher_id',
        );
    }

    /**
     * Get the mentor (user) associated with the registration.
     */
    public function mentor(): BelongsTo
    {
        return app(\Modules\User\Services\Contracts\UserService::class)->defineBelongsTo(
            $this,
            'mentor_id',
        );
    }

    /**
     * Get the internship program.
     */
    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    /**
     * Get the specific placement/industry partner.
     */
    public function placement(): BelongsTo
    {
        return $this->belongsTo(InternshipPlacement::class, 'placement_id');
    }

    /**
     * Alias for student (user relation).
     */
    public function student(): BelongsTo
    {
        return $this->user()->whereRelation('roles', 'name', 'student');
    }
}
