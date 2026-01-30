<?php

declare(strict_types=1);

namespace Modules\Mentor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Log\Concerns\HandlesAuditLog;
use Modules\Shared\Models\Concerns\HasUuid;

class MentoringVisit extends Model
{
    use HandlesAuditLog, HasUuid;

    protected $fillable = ['registration_id', 'teacher_id', 'visit_date', 'notes', 'findings'];

    protected $casts = [
        'visit_date' => 'date',
        'findings' => 'array',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(
            \Modules\Internship\Models\InternshipRegistration::class,
            'registration_id',
        );
    }

    public function teacher(): BelongsTo
    {
        return app(\Modules\User\Services\Contracts\UserService::class)->defineBelongsTo(
            $this,
            'teacher_id',
        );
    }
}
