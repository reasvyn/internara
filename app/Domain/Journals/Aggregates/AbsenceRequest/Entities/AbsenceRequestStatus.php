<?php

declare(strict_types=1);

namespace App\Domain\Journals\Aggregates\AbsenceRequest\Entities;

use App\Domain\Core\Entities\BaseEntity;
use App\Domain\Journals\Aggregates\AbsenceRequest\Enums\AbsenceRequestStatus as AbsenceRequestStatusEnum;
use Illuminate\Database\Eloquent\Model;

final readonly class AbsenceRequestStatus extends BaseEntity
{
    public function __construct(
        private ?AbsenceRequestStatusEnum $status,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            status: $model->status,
        );
    }

    public function isPending(): bool
    {
        return $this->status === AbsenceRequestStatusEnum::PENDING;
    }

    public function isProcessed(): bool
    {
        return $this->status?->isProcessed() ?? false;
    }
}
