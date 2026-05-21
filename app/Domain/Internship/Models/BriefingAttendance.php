<?php

declare(strict_types=1);

namespace App\Domain\Internship\Models;

use App\Domain\Core\Models\BaseModel;
use Database\Factories\BriefingAttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['briefing_id', 'user_id', 'attended', 'notes'])]
class BriefingAttendance extends BaseModel
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'attended' => 'boolean',
        ];
    }

    public function briefing(): BelongsTo
    {
        return $this->belongsTo(Briefing::class, 'briefing_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): BriefingAttendanceFactory
    {
        return BriefingAttendanceFactory::new();
    }
}
