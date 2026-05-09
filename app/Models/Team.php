<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'description', 'owner_id', 'is_active'])]
class Team extends BaseModel
{
    use HasFactory;

    protected $attributes = [
        'is_active' => true,
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')
            ->withPivot('role', 'assigned_by', 'assigned_at')
            ->withTimestamps();
    }

    public function mentors(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'mentor');
    }

    public function mentees(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'mentee');
    }
}
