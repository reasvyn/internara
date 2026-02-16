<?php

declare(strict_types=1);

namespace Modules\Status\Enums;

/**
 * Enum Status
 *
 * Defines the standard system states and their associated visual attributes.
 */
enum Status: string
{
    case ACTIVE = 'active';
    case PENDING = 'pending';
    case INACTIVE = 'inactive';

    /**
     * Get the visual color associated with the status.
     */
    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::PENDING => 'warning',
            self::INACTIVE => 'error',
        };
    }

    /**
     * Get the translation key for the status.
     */
    public function label(): string
    {
        return 'status::status.'.$this->value;
    }
}
