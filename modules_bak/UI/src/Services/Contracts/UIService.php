<?php

declare(strict_types=1);

namespace Modules\UI\Services\Contracts;

interface UIService
{
    public function getMenuItems(string $role): array;
    public function getThemeSettings(): array;
    public function setThemeSetting(string $key, $value): void;
}
