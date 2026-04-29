<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Department extends Model
{
    use HasUuid;

    protected $fillable = [
        'name',
        'description',
        'school_id',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
