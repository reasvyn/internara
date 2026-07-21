<?php

declare(strict_types=1);

namespace App\Journals\MonitoringVisit\Entities;

use App\Core\Entities\BaseEntity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class VisitState extends BaseEntity
{
    public function __construct(
        private bool $isVerified,
        private ?Carbon $visitDate,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            isVerified: (bool) ($model->is_verified ?? false),
            visitDate: $model->visit_date,
        );
    }

    public function canBeEdited(): bool
    {
        return ! $this->isVerified;
    }

    public function canBeDeleted(): bool
    {
        return ! $this->isVerified;
    }

    public function isRecent(?Carbon $now = null): bool
    {
        $now ??= new Carbon;

        return $this->visitDate && $this->visitDate->diffInDays($now) <= 7;
    }
}
