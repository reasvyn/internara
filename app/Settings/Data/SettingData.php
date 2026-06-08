<?php

declare(strict_types=1);

namespace App\Settings\Data;

use App\Core\Data\BaseData;

final readonly class SettingData extends BaseData
{
    public function __construct(
        public string $key,
        public mixed $value = null,
        public ?string $type = null,
        public ?string $group = null,
        public ?string $description = null,
    ) {}
}