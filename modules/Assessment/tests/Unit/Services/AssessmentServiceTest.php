<?php

declare(strict_types=1);

namespace Modules\Assessment\Tests\Unit\Services;

use Modules\Assessment\Models\Assessment;
use Modules\Assessment\Services\AssessmentService;
use Modules\Assessment\Services\Contracts\ComplianceService;
use Modules\Internship\Services\Contracts\RegistrationService;

test('it can query assessments', function () {
    $assessment = mock(Assessment::class);
    $service = new AssessmentService(
        mock(ComplianceService::class),
        mock(RegistrationService::class),
        $assessment,
    );

    $builder = mock(\Illuminate\Database\Eloquent\Builder::class);
    $assessment->shouldReceive('newQuery')->andReturn($builder);
    $builder->shouldReceive('select')->andReturnSelf();

    $result = $service->query();
    expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
});
