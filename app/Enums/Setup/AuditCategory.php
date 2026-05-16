<?php

declare(strict_types=1);

namespace App\Enums\Setup;

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
        $critical = config('setup.critical_categories', [
            self::Requirements,
            self::Permissions,
            self::Database,
        ]);

        return in_array($this, $critical, true);
    }
}
