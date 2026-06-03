<?php

declare(strict_types=1);

namespace App\Domain\Guidance\Aggregates\Handbook\Entities;

use App\Domain\Core\Entities\BaseEntity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class HandbookPublishState extends BaseEntity
{
    public function __construct(
        private bool $isActive,
        private ?Carbon $publishedAt,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            isActive: $model->is_active,
            publishedAt: $model->published_at,
        );
    }

    public function isPublished(): bool
    {
        return $this->isActive && $this->publishedAt !== null;
    }
}
