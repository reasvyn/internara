<?php

declare(strict_types=1);

namespace App\Core\Enums;

use App\Core\Contracts\LabelEnum;

enum AuditStatus: string implements LabelEnum
{
    case PASS = 'pass';
    case FAIL = 'fail';
    case WARN = 'warn';

    public function label(): string
    {
        return match ($this) {
            self::PASS => __('core.audit.status.pass'),
            self::FAIL => __('core.audit.status.fail'),
            self::WARN => __('core.audit.status.warn'),
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
