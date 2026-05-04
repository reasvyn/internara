<?php

declare(strict_types=1);

namespace App\Domain\School\Models;

use App\Domain\Core\Concerns\HasUuid;
use App\Domain\User\Models\Profile;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'school_id'])]
class Department extends Model
{
    use HasFactory, HasUuid;

    protected $with = ['school'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class);
    }
}
