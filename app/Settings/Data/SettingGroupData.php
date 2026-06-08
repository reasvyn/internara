<?php

declare(strict_types=1);

namespace App\Settings\Data;

use App\Core\Data\BaseData;

final readonly class SettingGroupData extends BaseData
{
    public function __construct(
        public string $name,
        public int $count = 0,
    ) {}
}