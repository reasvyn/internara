<?php

declare(strict_types=1);

namespace App\Domain\Admin\Aggregates\Announcement\Enums;

use App\Domain\Core\Contracts\LabelEnum;

enum AnnouncementStatus: string implements LabelEnum
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case PUBLISHED = 'published';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('announcement.status.draft'),
            self::SCHEDULED => __('announcement.status.scheduled'),
            self::PUBLISHED => __('announcement.status.published'),
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::DRAFT => in_array($target, [self::SCHEDULED, self::PUBLISHED], true),
            self::SCHEDULED => $target === self::PUBLISHED,
            self::PUBLISHED => false,
        };
    }

    public static function default(): self
    {
        return self::DRAFT;
    }
}
