<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\Logbook\Models;

use App\Modules\Core\Models\BaseModel;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journals\Domain\Logbook\Entities\LogbookState;
use App\Modules\Journals\Domain\Logbook\Enums\LogbookStatus;
use App\Modules\User\Models\User;
use Database\Factories\LogbookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;


/**
 * @property string $id
 * @property \Carbon\Carbon $date
 * @property bool $is_verified
 * @property \Carbon\Carbon $verified_at
 * @property \Carbon\Carbon $supervisor_reviewed_at
 * @property \App\Modules\Journals\Domain\Logbook\Enums\LogbookStatus $status
 * @property string $user_id
 * @property string $registration_id
 * @property string $content
 * @property string $learning_outcomes
 * @property string $verified_by
 * @property string $mentor_feedback
 * @property string $supervisor_note
 * @property string $supervisor_id
 * @property-read \App\Modules\User\Models\User|null $user
 * @property-read \App\Modules\Enrollment\Domain\Registration\Models\Registration|null $registration
 * @property-read \App\Modules\User\Models\User|null $verifier
 * @property-read \App\Modules\User\Models\User|null $supervisor
 */

#[
    Fillable([
        'user_id',
        'registration_id',
        'date',
        'content',
        'learning_outcomes',
        'status',
        'is_verified',
        'verified_by',
        'verified_at',
        'mentor_feedback',
        'supervisor_note',
        'supervisor_reviewed_at',
        'supervisor_id',
    ]),
]
class Logbook extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/heic',
                'image/heif',
            ]);
    }

    protected static function newFactory(): LogbookFactory
    {
        return LogbookFactory::new();
    }

    protected $casts = [
        'date' => 'date',
        'status' => LogbookStatus::class,
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'supervisor_reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function asLogbookState(): LogbookState
    {
        return LogbookState::fromModel($this);
    }
}
