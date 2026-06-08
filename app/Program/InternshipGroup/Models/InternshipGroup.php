<?php

declare(strict_types=1);

namespace App\Program\InternshipGroup\Models;

use App\Core\Models\BaseModel;
use App\Enrollment\Placement\Models\Placement;
use App\Program\InternshipGroup\Entities\InternshipGroupState;
use Database\Factories\InternshipGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'internship_id', 'placement_id', 'description', 'is_active'])]
class InternshipGroup extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(InternshipGroupMember::class);
    }

    public function asInternshipGroupState(): InternshipGroupState
    {
        return InternshipGroupState::fromModel($this);
    }

    protected static function newFactory(): InternshipGroupFactory
    {
        return InternshipGroupFactory::new();
    }
}
