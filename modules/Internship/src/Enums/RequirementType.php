<?php

declare(strict_types=1);

namespace Modules\Internship\Enums;

/**
 * Enum RequirementType
 *
 * Defines the types of prerequisites available in the internship system.
 */
enum RequirementType: string
{
    case DOCUMENT = 'document';
    case SKILL = 'skill';
    case CONDITION = 'condition';

    /**
     * Get the human-readable label for the requirement type.
     */
    public function label(): string
    {
        return match ($this) {
            self::DOCUMENT => __('internship::requirement.type.document'),
            self::SKILL => __('internship::requirement.type.skill'),
            self::CONDITION => __('internship::requirement.type.condition'),
        };
    }
}
