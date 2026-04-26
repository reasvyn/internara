<?php

declare(strict_types=1);

namespace Modules\Internship\Tests\Arch;

use Modules\Shared\Services\EloquentQuery;

test('placement manager should extend EloquentQuery')
    ->expect('Modules\Internship\Services\PlacementService')
    ->toExtend(EloquentQuery::class);

test('company service should extend EloquentQuery')
    ->expect('Modules\Internship\Services\CompanyService')
    ->toExtend(EloquentQuery::class);

/**
 * Note: Model isolation for InternshipPlacement is verified via 
 * System > Arch (DomainTest) in a more holistic manner.
 */
