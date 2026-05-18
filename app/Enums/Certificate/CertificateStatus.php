<?php

declare(strict_types=1);

namespace App\Enums\Certificate;

use App\Contracts\Shared\LabelEnum;

enum CertificateStatus: string implements LabelEnum
{
    case ISSUED = 'issued';
    case REVOKED = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::ISSUED => 'Issued',
            self::REVOKED => 'Revoked',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::REVOKED;
    }
}
