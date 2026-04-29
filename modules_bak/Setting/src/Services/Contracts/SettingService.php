<?php

declare(strict_types=1);

namespace Modules\Setting\Services\Contracts;

interface SettingService
{
    public function get(string $key, $default = null);
    public function set(string $key, $value): void;
    public function delete(string $key): void;
    public function getAll(): array;
}
