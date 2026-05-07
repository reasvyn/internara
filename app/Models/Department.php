<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'school_id'])]
class Department extends BaseModel
{
    use HasFactory;

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
