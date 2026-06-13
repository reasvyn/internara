<?php

declare(strict_types=1);

namespace App\SysAdmin\Announcement\Enums;

use App\Core\Contracts\StatusEnum;

enum AnnouncementStatus: string implements StatusEnum
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

    public function isTerminal(): bool
    {
        return match ($this) {
            self::PUBLISHED => true,
            default => false,
        };
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::SCHEDULED, self::PUBLISHED],
            self::SCHEDULED => [self::PUBLISHED],
            self::PUBLISHED => [],
        };
    }

    public static function default(): self
    {
        return self::DRAFT;
    }
}
