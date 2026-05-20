<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Models;

use App\Domain\Core\Models\BaseModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['presentation_id', 'examiner_id', 'score', 'feedback'])]
class PresentationExaminer extends BaseModel
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'score' => 'float',
        ];
    }

    public function presentation(): BelongsTo
    {
        return $this->belongsTo(Presentation::class, 'presentation_id');
    }

    public function examiner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'examiner_id');
    }
}
