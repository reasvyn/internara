<?php

declare(strict_types=1);

namespace App\Domain\Internship\Models;

use App\Domain\Core\Models\BaseModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'date', 'location', 'is_mandatory', 'internship_id', 'created_by'])]
class Briefing extends BaseModel
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'is_mandatory' => 'boolean',
        ];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class, 'internship_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(BriefingAttendance::class, 'briefing_id');
    }

    public static function hasStudentCompletedMandatoryBriefing(string $userId, string $internshipId): bool
    {
        $briefing = self::where('internship_id', $internshipId)
            ->where('is_mandatory', true)
            ->first();

        if (! $briefing) {
            return true;
        }

        return BriefingAttendance::where('briefing_id', $briefing->id)
            ->where('user_id', $userId)
            ->where('attended', true)
            ->exists();
    }
}
