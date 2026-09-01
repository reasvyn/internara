<?php

declare(strict_types=1);

namespace App\Modules\Core\Actions\Concerns;

trait ResolvesModuleName
{
    protected function moduleName(): string
    {
        $parts = explode('\\', static::class);

        if (count($parts) >= 3 && $parts[0] === 'App' && $parts[1] === 'Modules') {
            return $parts[2];
        }

        if (count($parts) >= 2 && $parts[0] === 'App') {
            return $parts[1];
        }

        return 'Unknown';
    }
}
