<?php

declare(strict_types=1);

namespace Modules\Journal\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Journal\Database\Factories\JournalEntryFactory;
use Modules\Log\Concerns\InteractsWithActivityLog;
use Modules\Media\Concerns\InteractsWithMedia;
use Modules\Shared\Models\Concerns\HasAcademicYear;
use Modules\Shared\Models\Concerns\HasStatus;
use Modules\Shared\Models\Concerns\HasUuid;
use Spatie\MediaLibrary\HasMedia;

class JournalEntry extends Model implements HasMedia
{
    use HasAcademicYear;
    use HasFactory;
    use HasStatus;
    use HasUuid;
    use InteractsWithActivityLog;
    use InteractsWithMedia;

    /**
     * The name of the activity log for this model.
     */
    protected string $activityLogName = 'journal';

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
        'work_topic',
        'activity_description',
        'basic_competence',
        'character_values',
        'reflection',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the registration associated with this journal entry.
     */
    public function registration(): BelongsTo
    {
        return app(
            \Modules\Internship\Services\Contracts\RegistrationService::class,
        )->defineBelongsTo($this, 'registration_id');
    }

    /**
     * Get the student (user) associated with this journal entry.
     */
    public function student(): BelongsTo
    {
        return app(\Modules\User\Services\Contracts\UserService::class)->defineBelongsTo(
            $this,
            'student_id',
        );
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): JournalEntryFactory
    {
        return JournalEntryFactory::new();
    }

    /**
     * Define media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')->useDisk('private');
    }
}
