<?php

declare(strict_types=1);

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Shared\Models\Concerns\HasAcademicYear;
use Modules\Shared\Models\Concerns\HasStatus;
use Modules\Shared\Models\Concerns\HasUuid;

class AttendanceLog extends Model
{
    use HasAcademicYear;
    use HasFactory;
    use HasStatus;
    use HasUuid;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'registration_id',
        'student_id',
        'academic_year',
        'date',
        'check_in_at',
        'check_out_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
    ];

    /**
     * Get the registration associated with this attendance log.
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(
            \Modules\Internship\Models\InternshipRegistration::class,
            'registration_id',
        );
    }

    /**
     * Get the student (user) associated with this attendance log.
     */
    public function student(): BelongsTo
    {
        return app(\Modules\User\Services\Contracts\UserService::class)->defineBelongsTo(
            $this,
            'student_id',
        );
    }
}
