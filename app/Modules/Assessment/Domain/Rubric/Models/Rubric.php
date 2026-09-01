<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Domain\Rubric\Models;

use App\Modules\Assessment\Models\Assessment;
use App\Modules\Core\Models\BaseModel;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Models\User;
use Database\Factories\RubricFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


/**
 * @property string $id
 * @property array $structure
 * @property bool $is_active
 * @property string $internship_id
 * @property string $name
 * @property string $created_by
 * @property-read \App\Modules\Program\Domain\Internship\Models\Internship|null $internship
 * @property-read \App\Modules\User\Models\User|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int,\App\Modules\Assessment\Models\Assessment> $assessments
 */

#[Fillable(['internship_id', 'name', 'structure', 'is_active', 'created_by'])]
class Rubric extends BaseModel
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'structure' => 'json',
            'is_active' => 'boolean',
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
