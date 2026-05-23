<?php

declare(strict_types=1);

use App\Domain\Shared\Support\CsvHandler;
use App\Domain\Shared\Support\Environment;
use App\Domain\Shared\Support\Locale;
use App\Domain\Shared\Support\Theme;

arch('Shared utilities are final classes')
    ->expect(Environment::class)
    ->toBeFinal()
    ->and(Locale::class)
    ->toBeFinal()
    ->and(Theme::class)
    ->toBeFinal()
    ->and(CsvHandler::class)
    ->toBeFinal();

arch('Shared utilities have strict types')
    ->expect(Environment::class)
    ->toUseStrictTypes()
    ->and(Locale::class)
    ->toUseStrictTypes()
    ->and(Theme::class)
    ->toUseStrictTypes()
    ->and(CsvHandler::class)
    ->toUseStrictTypes();
