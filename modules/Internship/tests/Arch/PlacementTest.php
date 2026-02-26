<?php

declare(strict_types=1);

namespace Modules\Internship\Tests\Arch;

use Modules\Shared\Services\EloquentQuery;
use Modules\Shared\Services\BaseService;

test('placement manager should extend BaseService')
    ->expect('Modules\Internship\Services\PlacementService')
    ->toExtend(BaseService::class);

test('company service should extend EloquentQuery')
    ->expect('Modules\Internship\Services\CompanyService')
    ->toExtend(EloquentQuery::class);

test('placement models should be isolated')
    ->expect('Modules\Internship\Models\InternshipPlacement')
    ->not->toBeUsedOutside([
        'Modules\Internship',
        'Modules\Shared',
    ]);
