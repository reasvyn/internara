<?php

declare(strict_types=1);

namespace Modules\School\Tests\Arch;

test('department models should not hold direct relationships to internships')
    ->expect('Modules\Department\Models\Department')
    ->not->toDependOn('Modules\Internship\Models');

test('external data access to school should use service contract')
    ->expect('Modules\School\Services\Contracts\SchoolService')
    ->toBeInterface();

test('institutional support utilities should be final')
    ->expect('Modules\School\Support')
    ->toBeFinal();
