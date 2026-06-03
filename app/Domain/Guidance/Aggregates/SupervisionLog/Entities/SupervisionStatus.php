<?php

declare(strict_types=1);

namespace App\Domain\Guidance\Aggregates\SupervisionLog\Entities;

use App\Domain\Core\Entities\BaseEntity;
use App\Domain\Guidance\Aggregates\SupervisionLog\Enums\SupervisionLogStatus;
use Illuminate\Database\Eloquent\Model;

final readonly class SupervisionStatus extends BaseEntity
{
    public function __construct(
        private ?SupervisionLogStatus $status,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            status: $model->status,
        );
    }

    public function isCompleted(): bool
    {
        return $this->status?->isTerminal() ?? false;
    }

    public function isActive(): bool
    {
        return $this->status?->isActive() ?? false;
    }
}
