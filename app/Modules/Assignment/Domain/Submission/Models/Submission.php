<?php

declare(strict_types=1);

namespace App\Modules\Assignment\Domain\Submission\Models;

use App\Modules\Assignment\Domain\Submission\Entities\SubmissionState;
use App\Modules\Assignment\Domain\Submission\Enums\SubmissionStatus;
use App\Modules\Assignment\Models\Assignment;
use App\Modules\Core\Models\BaseModel;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Models\User;
use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Student submission for an assignment.
 *
 * S1 - Secure: File upload validation via Spatie Media Library.
 * S2 - Sustain: Status tracking with rich model methods.
 */

/**
 * @property string $id
 * @property array $metadata
 * @property \Carbon\Carbon $submitted_at
 * @property \Carbon\Carbon $graded_at
 * @property \App\Modules\Assignment\Domain\Submission\Enums\SubmissionStatus $status
 * @property string $assignment_id
 * @property string $registration_id
 * @property string $student_id
 * @property string $content
 * @property string $score
 * @property string $feedback
 * @property string $graded_by
 * @property string $verified_by
 * @property string $verified_at
 * @property-read \App\Modules\Assignment\Models\Assignment|null $assignment
 * @property-read \App\Modules\Enrollment\Domain\Registration\Models\Registration|null $registration
 * @property-read \App\Modules\User\Models\User|null $student
 * @property-read \App\Modules\User\Models\User|null $grader
 */

#[
    Fillable([
        'assignment_id',
        'registration_id',
        'student_id',
        'content',
        'metadata',
        'status',
        'submitted_at',
        'score',
        'feedback',
        'graded_by',
        'graded_at',
        'verified_by',
        'verified_at',
    ]),
]
class Submission extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $casts = [
        'metadata' => 'array',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'status' => SubmissionStatus::class,
    ];

    /**
     * Get the assignment.
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * Get the internship registration.
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    /**
     * Get the student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the user who graded this submission.
     */
    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function asSubmissionState(): SubmissionState
    {
        return SubmissionState::fromModel($this);
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile();
    }

    /**
     * Create a new factory instance.
     */
    protected static function newFactory(): SubmissionFactory
    {
        return SubmissionFactory::new();
    }
}
