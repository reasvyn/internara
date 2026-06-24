<?php

declare(strict_types=1);

namespace App\Assessment\Rubric\Models;

use App\Assessment\Models\Assessment;
use App\Core\Models\BaseModel;
use App\Program\Internship\Models\Internship;
use App\User\Models\User;
use Database\Factories\RubricFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['internship_id', 'name', 'structure', 'created_by'])]
class Rubric extends BaseModel
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'structure' => 'json',
        ];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    protected static function newFactory(): RubricFactory
    {
        return RubricFactory::new();
    }
}
