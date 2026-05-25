<?php

declare(strict_types=1);

namespace App\Domain\Certificate\Enums;

use App\Domain\Core\Contracts\StatusEnum;

enum CertificateStatus: string implements StatusEnum
{
    case ISSUED = 'issued';
    case REVOKED = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::ISSUED => __('Issued'),
            self::REVOKED => __('Revoked'),
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::REVOKED;
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::ISSUED => [self::REVOKED],
            self::REVOKED => [],
        };
    }

    public function canTransitionTo(StatusEnum $target): bool
    {
        if (! $target instanceof self) {
            return false;
        }

        return in_array($target, $this->validTransitions(), true);
    }
}
