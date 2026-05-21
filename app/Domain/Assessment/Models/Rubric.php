<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Models;

use App\Domain\Core\Models\BaseModel;
use App\Domain\User\Models\User;
use Database\Factories\RubricFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['internship_id', 'name', 'description', 'is_active', 'created_by'])]
class Rubric extends BaseModel
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function competencies(): HasMany
    {
        return $this->hasMany(Competency::class)->orderBy('order');
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
