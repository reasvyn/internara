<?php

declare(strict_types=1);

namespace App\Entities\GeneratedReport;

use App\Entities\BaseEntity;
use Illuminate\Database\Eloquent\Model;

final readonly class ReportStatus extends BaseEntity
{
    public function __construct(
        private string $status,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            status: $model->status,
        );
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
