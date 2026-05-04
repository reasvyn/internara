<?php

declare(strict_types=1);

namespace App\Domain\Logbook\Models;

use App\Domain\Core\Concerns\HasUuid;
use App\Enums\LogbookEntryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'registration_id', 'date', 'content', 'learning_outcomes', 'status', 'is_verified', 'verified_by', 'verified_at', 'mentor_feedback'])]
class LogbookEntry extends Model
{
    use HasFactory, HasUuid;

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

    public function isVerified(): bool
    {
        return $this->status === LogbookEntryStatus::VERIFIED;
    }

    public function canBeEdited(): bool
    {
        return in_array(
            $this->status,
            [LogbookEntryStatus::DRAFT, LogbookEntryStatus::REVISION_REQUIRED],
            true,
        );
    }
}
