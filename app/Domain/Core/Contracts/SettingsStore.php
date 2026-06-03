<?php

declare(strict_types=1);

namespace App\Domain\Core\Contracts;

interface SettingsStore
{
    /**
     * Retrieve a setting value by key.
     */
    public function get(string $key, mixed $default = null): mixed;
}
