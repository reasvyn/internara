<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enterprise account lifecycle states.
 */
enum AccountStatus: string
{
    case PENDING = 'pending';
    case ACTIVATED = 'activated';
    case VERIFIED = 'verified';
    case PROTECTED = 'protected';
    case RESTRICTED = 'restricted';
    case SUSPENDED = 'suspended';
    case INACTIVE = 'inactive';
    case ARCHIVED = 'archived';

    public function isActive(): bool
    {
        return in_array($this, [self::VERIFIED, self::PROTECTED, self::ACTIVATED]);
    }

    public function isProblem(): bool
    {
        return in_array($this, [self::RESTRICTED, self::SUSPENDED]);
    }
}
