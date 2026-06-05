<?php

declare(strict_types=1);

namespace App\Journals\IndustryAssessment\Models;

use App\Core\Models\BaseModel;
use App\Enrollment\Models\Registration;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['registration_id', 'supervisor_id', 'score', 'rubric_data', 'notes', 'submitted_at'])]
class IndustryAssessment extends BaseModel
{
    protected $casts = [
        'score' => 'decimal:2',
        'rubric_data' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
