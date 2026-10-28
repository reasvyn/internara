<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Models;

use App\Modules\Core\Models\BaseModel;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $registration_id
 * @property string $token
 * @property Carbon $expires_at
 * @property Carbon|null $submitted_at
 * @property string|null $evaluator_name
 * @property string|null $evaluator_phone
 * @property string|null $feedback
 * @property string|null $created_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Registration|null $registration
 * @property-read User|null $creator
 */
#[
    Fillable([
        'registration_id',
        'token',
        'expires_at',
        'submitted_at',
        'evaluator_name',
        'evaluator_phone',
        'feedback',
        'created_by',
    ]),
]
class AssessmentMagicLink extends BaseModel
{
    use HasFactory;

    protected $table = 'assessment_magic_links';

    protected $casts = [
        'expires_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isSubmitted();
    }
}
