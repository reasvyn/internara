<?php

declare(strict_types=1);

namespace App\Domain\Reports\Aggregates\Report\Models;

use App\Domain\Core\Models\BaseModel;
use App\Domain\Reports\Aggregates\Report\Enums\ReportStatus;
use App\Domain\User\Models\User;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['registration_id', 'title', 'status', 'chapter_structure', 'content', 'submitted_at', 'graded_by', 'graded_at', 'score', 'feedback', 'supervisor_notes'])]
class Report extends BaseModel
{
    use HasFactory;

    protected $attributes = [
        'status' => ReportStatus::DRAFT->value,
    ];

    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'chapter_structure' => 'array',
            'content' => 'array',
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
            'score' => 'float',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ReportRevision::class, 'report_id');
    }

    protected static function newFactory(): ReportFactory
    {
        return ReportFactory::new();
    }
}
