<?php

declare(strict_types=1);

namespace Modules\Assignment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Log\Concerns\InteractsWithActivityLog;
use Modules\Media\Concerns\InteractsWithMedia;
use Modules\Shared\Models\Concerns\HasUuid;
use Modules\Status\Concerns\HasStatuses;
use Modules\User\Services\Contracts\UserService;
use Spatie\MediaLibrary\HasMedia;

class Submission extends Model implements HasMedia
{
    use HasStatuses;
    use HasUuid;
    use InteractsWithActivityLog;
    use InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'assignment_id',
        'registration_id',
        'student_id',
        'content',
        'metadata',
        'submitted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'submitted_at' => 'datetime',
    ];

    /**
     * The name of the activity log for this model.
     */
    protected string $activityLogName = 'submission';

    /**
     * Get the assignment associated with this submission.
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * Get the student (user) associated with this submission.
     */
    public function student(): BelongsTo
    {
        return app(UserService::class)->defineBelongsTo($this, 'student_id', relation: 'student');
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile();
    }
}
