<?php

declare(strict_types=1);

namespace App\Journals\Logbook\Models;

use App\Core\Models\BaseModel;
use App\Enrollment\Registration\Models\Registration;
use App\Journals\Logbook\Entities\LogbookState;
use App\Journals\Logbook\Enums\LogbookStatus;
use App\User\Models\User;
use Database\Factories\LogbookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

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
            ])
            ->maxFileSize(10 * 1024 * 1024);
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
