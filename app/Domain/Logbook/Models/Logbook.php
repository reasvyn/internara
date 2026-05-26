<?php

declare(strict_types=1);

namespace App\Domain\Logbook\Models;

use App\Domain\Core\Models\BaseModel;
use App\Domain\Logbook\Entities\LogbookState;
use App\Domain\Logbook\Enums\LogbookStatus;
use App\Domain\Registration\Models\Registration;
use App\Domain\User\Models\User;
use Database\Factories\LogbookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'registration_id', 'date', 'content', 'learning_outcomes', 'status', 'is_verified', 'verified_by', 'verified_at', 'mentor_feedback'])]
class Logbook extends BaseModel
{
    use HasFactory;

    protected static function newFactory(): LogbookFactory
    {
        return LogbookFactory::new();
    }

    protected $casts = [
        'date' => 'date',
        'status' => LogbookStatus::class,
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

    public function asLogbookState(): LogbookState
    {
        return LogbookState::fromModel($this);
    }
}
