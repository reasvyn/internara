<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum AuditStatus: string
{
    case Pass = 'pass';
    case Fail = 'fail';
    case Warn = 'warn';

    public function label(): string
    {
        return match ($this) {
            self::Pass => 'Pass',
            self::Fail => 'Fail',
            self::Warn => 'Warn',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::Pass => '✓',
            self::Fail => '✗',
            self::Warn => '⚠',
        };
    }
}
