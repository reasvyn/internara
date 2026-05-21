<?php

declare(strict_types=1);

namespace App\Domain\Internship\Models;

use App\Domain\Core\Models\BaseModel;
use App\Domain\User\Models\User;
use Database\Factories\ReportRevisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['report_id', 'round', 'feedback', 'requested_by', 'requested_at', 'resubmitted_at'])]
class ReportRevision extends BaseModel
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'resubmitted_at' => 'datetime',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'report_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    protected static function newFactory(): ReportRevisionFactory
    {
        return ReportRevisionFactory::new();
    }
}
