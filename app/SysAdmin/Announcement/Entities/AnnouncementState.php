<?php

declare(strict_types=1);

namespace App\SysAdmin\Announcement\Entities;

use App\Core\Entities\BaseEntity;
use App\SysAdmin\Announcement\Enums\AnnouncementStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final readonly class AnnouncementState extends BaseEntity
{
    public function __construct(
        private AnnouncementStatus $status,
        private ?Carbon $scheduledAt,
    ) {}

    public static function fromModel(Model $model): static
    {
        return new self(
            status: $model->status instanceof AnnouncementStatus
                ? $model->status
                : AnnouncementStatus::from($model->status),
            scheduledAt: $model->scheduled_at,
        );
    }

    public function isPublished(): bool
    {
        return $this->status === AnnouncementStatus::PUBLISHED;
    }

    public function isDraft(): bool
    {
        return $this->status === AnnouncementStatus::DRAFT;
    }

    public function isScheduled(): bool
    {
        return $this->status === AnnouncementStatus::SCHEDULED;
    }

    public function isPendingPublish(?Carbon $now = null): bool
    {
        $now ??= new Carbon;

        return $this->status === AnnouncementStatus::SCHEDULED
            && $this->scheduledAt !== null
            && $this->scheduledAt->lte($now);
    }
}
