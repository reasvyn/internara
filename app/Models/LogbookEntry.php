<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\LogbookEntry\LogbookEntryState;
use App\Enums\Logbook\LogbookEntryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'registration_id', 'date', 'content', 'learning_outcomes', 'status', 'is_verified', 'verified_by', 'verified_at', 'mentor_feedback'])]
class LogbookEntry extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'date' => 'date',
        'status' => LogbookEntryStatus::class,
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
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

    public function entity(): LogbookEntryState
    {
        return LogbookEntryState::fromModel($this);
    }

    public function isVerified(): bool
    {
        return $this->entity()->isVerified();
    }

    public function canBeEdited(): bool
    {
        return $this->entity()->canBeEdited();
    }
}
