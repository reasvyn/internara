<?php

declare(strict_types=1);

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Log\Concerns\InteractsWithActivityLog;
use Modules\Shared\Models\Concerns\HasUuid;
use Modules\Status\Concerns\HasStatus;

class AbsenceRequest extends Model
{
    use HasFactory, HasStatus, HasUuid, InteractsWithActivityLog;

    protected $fillable = [
        'registration_id',
        'student_id',
        'date',
        'type',
        'reason',
        'proof_url',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    protected string $activityLogName = 'absence_request';

    public function registration(): BelongsTo
    {
        return $this->belongsTo(
            \Modules\Internship\Models\InternshipRegistration::class,
            'registration_id',
        );
    }

    public function student(): BelongsTo
    {
        return app(\Modules\User\Services\Contracts\UserService::class)->defineBelongsTo(
            $this,
            'student_id',
        );
    }
}
