<?php

declare(strict_types=1);

namespace App\Journals\Schedule\Entities;

use App\Core\Entities\BaseEntity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class ScheduleStatus extends BaseEntity
{
    public function __construct(
        private Carbon $startAt,
        private ?Carbon $endAt,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            startAt: $model->start_at,
            endAt: $model->end_at,
        );
    }

    public function isOngoing(?Carbon $now = null): bool
    {
        $now ??= new Carbon;

        return $this->startAt <= $now && ($this->endAt === null || $this->endAt >= $now);
    }

    public function isUpcoming(?Carbon $now = null): bool
    {
        $now ??= new Carbon;

        return $this->startAt > $now;
    }
}
