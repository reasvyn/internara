<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Arch;

use Modules\Shared\Services\BaseService;

test('setup module should not depend on domain modules')
    ->expect('Modules\Setup')
    ->not->toUse([
        'Modules\Internship\Services\InternshipService',
        'Modules\Journal\Services\JournalService',
        'Modules\Attendance\Services\AttendanceService',
        'Modules\Assessment\Services\AssessmentService',
        'Modules\Report\Services\ReportService',
        'Modules\Guidance\Services\GuidanceService',
        'Modules\Assignment\Services\AssignmentService',
    ]);

test('installer service should extend BaseService')
    ->expect('Modules\Setup\Services\InstallerService')
    ->toExtend(BaseService::class);

test('setup support utilities should be final')->expect('Modules\Setup\Support')->toBeFinal();
