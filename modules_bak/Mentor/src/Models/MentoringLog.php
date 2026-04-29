<?php

declare(strict_types=1);

namespace Modules\Mentor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Log\Concerns\InteractsWithActivityLog;
use Modules\Shared\Models\Concerns\HasUuid;
use Modules\User\Services\Contracts\UserService;

class MentoringLog extends Model
{
    use HasUuid, InteractsWithActivityLog;

    protected $fillable = [
        'registration_id',
        'causer_id',
        'type',
        'subject',
        'content',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected string $activityLogName = 'mentoring';

    public function registration(): BelongsTo
    {
        return $this->belongsTo(InternshipRegistration::class, 'registration_id');
    }

    public function causer(): BelongsTo
    {
        return app(UserService::class)->defineBelongsTo($this, 'causer_id');
    }
}
