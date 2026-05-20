<?php

declare(strict_types=1);

namespace App\Domain\Core\Enums;

use App\Domain\Core\Contracts\LabelEnum;

enum AuditStatus: string implements LabelEnum
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
