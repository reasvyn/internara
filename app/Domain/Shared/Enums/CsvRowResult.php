<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

use App\Domain\Core\Contracts\LabelEnum;

enum CsvRowResult: string implements LabelEnum
{
    case CREATED = 'created';
    case SKIPPED = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::CREATED => __('shared.csv.created'),
            self::SKIPPED => __('shared.csv.skipped'),
        };
    }
}
