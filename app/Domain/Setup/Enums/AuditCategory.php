<?php

declare(strict_types=1);

namespace App\Domain\Setup\Enums;

enum AuditCategory: string
{
    case Requirements = 'requirements';
    case Permissions = 'permissions';
    case Database = 'database';
    case Terminal = 'terminal';
    case Recommendations = 'recommendations';

    public function label(): string
    {
        return __("setup.wizard.{$this->value}");
    }

    public function isCritical(): bool
    {
        return in_array($this, [self::Requirements, self::Permissions, self::Database], true);
    }
}
