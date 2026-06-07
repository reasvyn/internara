<?php

declare(strict_types=1);

namespace App\Assignment\Submission\Models;

use App\Assignment\Submission\Entities\SubmissionState;
use App\Assignment\Submission\Enums\SubmissionStatus;
use App\Core\Models\BaseModel;
use App\Enrollment\Models\Registration;
use App\User\Models\User;
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
