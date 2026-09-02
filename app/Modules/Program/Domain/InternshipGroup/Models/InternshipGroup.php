<?php

declare(strict_types=1);

namespace App\Modules\Program\Domain\InternshipGroup\Models;

use App\Modules\Core\Models\BaseModel;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\Program\Domain\InternshipGroup\Entities\InternshipGroupState;
use Database\Factories\InternshipGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property bool $is_active
 * @property string $name
 * @property string $internship_id
 * @property string $placement_id
 * @property string $description
 * @property-read Internship|null $internship
 * @property-read Placement|null $placement
 * @property-read Collection<int,InternshipGroupMember> $members
 */
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
