<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\AbsenceRequest\Entities;

use App\Modules\Core\Entities\BaseEntity;
use App\Modules\Journals\Domain\AbsenceRequest\Enums\AbsenceRequestStatus as AbsenceRequestStatusEnum;
use Illuminate\Database\Eloquent\Model;

final readonly class AbsenceRequestState extends BaseEntity
{
    public function __construct(private ?AbsenceRequestStatusEnum $status) {}

    public static function fromModel(Model $model): static
    {
        return new self(status: $model->status);
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
