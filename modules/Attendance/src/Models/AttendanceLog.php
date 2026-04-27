<?php

declare(strict_types=1);

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Academic\Models\Concerns\HasAcademicYear;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\Log\Concerns\InteractsWithActivityLog;
use Modules\Shared\Models\Concerns\HasUuid;
use Modules\Status\Concerns\HasStatuses;
use Modules\User\Services\Contracts\UserService;

class AttendanceLog extends Model
{
    use HasAcademicYear;
    use HasFactory;
    use HasStatuses;
    use HasUuid;
    use InteractsWithActivityLog;

    /**
     * The name of the activity log for this model.
     */
    protected string $activityLogName = 'attendance';

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
        'notes',
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
        return app(RegistrationService::class)->defineBelongsTo($this, 'registration_id');
    }

    /**
     * Get the student (user) associated with this attendance log.
     */
    public function student(): BelongsTo
    {
        return app(UserService::class)->defineBelongsTo($this, 'student_id', relation: 'student');
    }
}
