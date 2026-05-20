<?php

declare(strict_types=1);

namespace App\Domain\Mentor\Models;

use App\Domain\Core\Models\BaseModel;
use App\Domain\Mentor\Entities\MentorRole;
use App\Domain\Registration\Models\Registration;
use App\Domain\User\Models\User;
use Database\Factories\MentorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['user_id', 'type', 'is_active'])]
class Mentor extends BaseModel
{
    use HasFactory;

    protected static function newFactory(): MentorFactory
    {
        return MentorFactory::new();
    }

    const TYPE_SCHOOL_TEACHER = 'school_teacher';

    const TYPE_INDUSTRY_SUPERVISOR = 'industry_supervisor';

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registrations(): BelongsToMany
    {
        return $this->belongsToMany(Registration::class, 'registration_mentor', 'mentor_id', 'registration_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function asMentorRole(): MentorRole
    {
        return MentorRole::fromModel($this);
    }
}
