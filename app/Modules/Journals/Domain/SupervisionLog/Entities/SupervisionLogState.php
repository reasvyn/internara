<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\SupervisionLog\Entities;

use App\Modules\Core\Entities\BaseEntity;
use App\Modules\Journals\Domain\SupervisionLog\Enums\SupervisionLogStatus;
use App\Modules\Journals\Domain\SupervisionLog\Models\SupervisionLog;
use Carbon\Carbon;

final readonly class SupervisionLogState extends BaseEntity
{
    public function __construct(
        private SupervisionLogStatus $status,
        private ?Carbon $submittedAt,
        private ?Carbon $reviewedAt,
    ) {}

    public static function fromModel(SupervisionLog $model): static
    {
        $created = $model->created_at;

        return new self(
            status: $model->status,
            submittedAt: $model->status === SupervisionLogStatus::SUBMITTED ? $created : null,
            reviewedAt: $model->reviewed_at,
        );
    }

    public function canBeEdited(): bool
    {
        return $this->status === SupervisionLogStatus::DRAFT;
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === SupervisionLogStatus::DRAFT;
    }

    public function needsAcknowledgment(): bool
    {
        return $this->status === SupervisionLogStatus::REVIEWED;
    }
}
