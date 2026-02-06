<?php

declare(strict_types=1);

namespace Modules\Schedule\Enums;

/**
 * Enum ScheduleType
 *
 * Defines the authoritative types of institutional schedule milestones.
 */
enum ScheduleType: string
{
    case EVENT = 'event';
    case DEADLINE = 'deadline';
    case BRIEFING = 'briefing';

    /**
     * Get the localized label for the schedule type.
     */
    public function label(): string
    {
        return __("schedule::enums.types.{$this->value}");
    }
}
