<?php

declare(strict_types=1);

namespace Modules\Internship\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Shared\Models\Concerns\HasUuid;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class RequirementSubmission extends Model implements HasMedia
{
    use HasFactory;
    use HasUuid;
    use InteractsWithMedia;

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
        'requirement_id',
        'value',
        'status',
        'notes',
        'verified_at',
        'verified_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => \Modules\Internship\Enums\SubmissionStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Get the registration associated with the submission.
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(InternshipRegistration::class, 'registration_id');
    }

    /**
     * Get the requirement definition associated with the submission.
     */
    public function requirement(): BelongsTo
    {
        return $this->belongsTo(InternshipRequirement::class, 'requirement_id');
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('document')->singleFile();
    }
}
