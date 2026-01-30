<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Services;

use Modules\Assessment\Services\Contracts\AssessmentService;
use Modules\Core\Services\AnalyticsAggregator;
use Modules\Internship\Services\Contracts\InternshipPlacementService;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\Journal\Services\Contracts\JournalService;
use Modules\Setting\Facades\Setting;

test('it calculates institutional summary', function () {
    $registrationService = mock(RegistrationService::class);
    $placementService = mock(InternshipPlacementService::class);
    $journalService = mock(JournalService::class);
    $assessmentService = mock(AssessmentService::class);

    Setting::shouldReceive('getValue')
        ->with('active_academic_year', \Mockery::any(), \Mockery::any())
        ->andReturn('2025/2026');

    $builder = mock(\Illuminate\Database\Eloquent\Builder::class);
    $registrationService->shouldReceive('query')->andReturn($builder);
    $builder->shouldReceive('count')->andReturn(10);
    $placementService->shouldReceive('all')->andReturn(collect([1, 2, 3]));

    $aggregator = new AnalyticsAggregator(
        $registrationService,
        $placementService,
        $journalService,
        $assessmentService,
    );

    $summary = $aggregator->getInstitutionalSummary();

    expect($summary['total_interns'])->toBe(10)->and($summary['active_partners'])->toBe(3);
});
