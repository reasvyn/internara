<?php

declare(strict_types=1);

namespace Modules\Assessment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Shared\Models\Concerns\HasUuid;
use Modules\User\Models\User;

class Assessment extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'registration_id',
        'academic_year',
        'evaluator_id',
        'type',
        'score',
        'content',
        'feedback',
        'finalized_at',
    ];

    protected $casts = [
        'content' => 'array',
        'score' => 'float',
        'finalized_at' => 'datetime',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(InternshipRegistration::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function isFinalized(): bool
    {
        return ! is_null($this->finalized_at);
    }
}
