<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

enum CsvRowResult: string
{
    case CREATED = 'created';
    case SKIPPED = 'skipped';
}
