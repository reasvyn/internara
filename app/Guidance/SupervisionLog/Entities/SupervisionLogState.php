<?php

declare(strict_types=1);

namespace App\Guidance\SupervisionLog\Entities;

use App\Core\Entities\BaseEntity;
use App\Guidance\SupervisionLog\Enums\SupervisionLogStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class SupervisionLogState extends BaseEntity
{
    public function __construct(
        private SupervisionLogStatus $status,
        private ?Carbon $submittedAt,
        private ?Carbon $reviewedAt,
    ) {}

    public static function fromModel(Model $model): static
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
