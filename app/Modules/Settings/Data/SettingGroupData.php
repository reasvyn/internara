<?php

declare(strict_types=1);

namespace App\Modules\Settings\Data;

use App\Modules\Core\Data\BaseData;

final readonly class SettingGroupData extends BaseData
{
    public function __construct(
        public string $name,
        public int $count = 0,
    ) {}
}
