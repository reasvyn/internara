<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Arch;

use Modules\Shared\Services\BaseService;

test('setup module should not depend on domain modules')
    ->expect('Modules\Setup')
    ->not->toDependOn([
        'Modules\Internship',
        'Modules\Journal',
        'Modules\Attendance',
        'Modules\Assessment',
        'Modules\Report',
        'Modules\Guidance',
        'Modules\Assignment',
    ]);

test('installer service should extend BaseService')
    ->expect('Modules\Setup\Services\InstallerService')
    ->toExtend(BaseService::class);

test('setup support utilities should be final')
    ->expect('Modules\Setup\Support')
    ->toBeFinal();
