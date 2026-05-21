<?php

declare(strict_types=1);

namespace App\Domain\Core\Enums;

use App\Domain\Core\Contracts\LabelEnum;

enum AuditStatus: string implements LabelEnum
{
    case PASS = 'pass';
    case FAIL = 'fail';
    case WARN = 'warn';

    public function label(): string
    {
        return match ($this) {
            self::PASS => 'Pass',
            self::FAIL => 'Fail',
            self::WARN => 'Warn',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::PASS => '✓',
            self::FAIL => '✗',
            self::WARN => '⚠',
        };
    }
}
