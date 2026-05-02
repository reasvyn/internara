<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubmissionStatus;
use App\Models\Concerns\HasUuid;
use Database\Factories\SubmissionFactory;
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
class Submission extends Model implements HasMedia
{
    use HasFactory, HasUuid, InteractsWithMedia;

    protected $fillable = [
        'assignment_id',
        'registration_id',
        'student_id',
        'content',
        'metadata',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'submitted_at' => 'datetime',
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
        return $this->belongsTo(InternshipRegistration::class, 'registration_id');
    }

    /**
     * Get the student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Check if submission can be edited.
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, [
            SubmissionStatus::DRAFT,
            SubmissionStatus::REVISION_REQUIRED,
        ], true);
    }

    /**
     * Check if submission is verified.
     */
    public function isVerified(): bool
    {
        return $this->status === SubmissionStatus::VERIFIED;
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
