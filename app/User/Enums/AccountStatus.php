<?php

declare(strict_types=1);

namespace App\User\Enums;

use App\Core\Contracts\ColorableEnum;
use App\Core\Contracts\StatusEnum;

enum AccountStatus: string implements ColorableEnum, StatusEnum
{
    case PROVISIONED = 'provisioned';
    case ACTIVATED = 'activated';
    case VERIFIED = 'verified';
    case PROTECTED = 'protected';
    case RESTRICTED = 'restricted';
    case SUSPENDED = 'suspended';
    case INACTIVE = 'inactive';
    case ARCHIVED = 'archived';

    public function color(): string
    {
        return match ($this) {
            self::PROVISIONED => 'warning',
            self::ACTIVATED => 'info',
            self::VERIFIED => 'success',
            self::PROTECTED => 'primary',
            self::RESTRICTED => 'warning',
            self::SUSPENDED => 'error',
            self::INACTIVE => 'warning',
            self::ARCHIVED => 'error',
        };
    }

    public function label(): string
    {
        return __('account_status.status.'.$this->value);
    }

    public function allowsLogin(): bool
    {
        return match ($this) {
            self::PROVISIONED => false,
            self::ACTIVATED => true,
            self::VERIFIED => true,
            self::PROTECTED => true,
            self::RESTRICTED => true,
            self::SUSPENDED => false,
            self::INACTIVE => true,
            self::ARCHIVED => false,
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::ARCHIVED, self::PROTECTED => true,
            default => false,
        };
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::PROVISIONED => [self::ACTIVATED, self::SUSPENDED],
            self::ACTIVATED => [self::VERIFIED, self::SUSPENDED, self::ARCHIVED],
            self::VERIFIED => [self::RESTRICTED, self::SUSPENDED, self::ARCHIVED, self::INACTIVE],
            self::PROTECTED => [],
            self::RESTRICTED => [self::VERIFIED, self::SUSPENDED, self::ARCHIVED],
            self::SUSPENDED => [self::ACTIVATED, self::VERIFIED, self::ARCHIVED],
            self::INACTIVE => [self::VERIFIED, self::ARCHIVED, self::SUSPENDED],
            self::ARCHIVED => [],
        };
    }

    public function canTransitionTo(StatusEnum $target): bool
    {
        if (! ($target instanceof self)) {
            return false;
        }
        if ($this->isTerminal()) {
            return false;
        }

        return in_array($target, $this->validTransitions(), strict: true);
    }
}
