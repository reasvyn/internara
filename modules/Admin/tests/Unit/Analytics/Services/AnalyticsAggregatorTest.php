<?php

declare(strict_types=1);

namespace Modules\Admin\Tests\Unit\Analytics\Services;

use Modules\Admin\Analytics\Services\AnalyticsAggregator;
use Modules\Assessment\Services\Contracts\AssessmentService;
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

    // Bypass actual caching to avoid missing table issues in test environment
    \Illuminate\Support\Facades\Cache::shouldReceive('remember')
        ->once()
        ->andReturnUsing(fn ($key, $ttl, $callback) => $callback());

    $builder = mock(\Illuminate\Database\Eloquent\Builder::class);
    $registrationService->shouldReceive('query')->andReturn($builder);
    $builder->shouldReceive('with')->andReturnSelf();
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

test('it identifies at risk students', function () {
    $registrationService = mock(RegistrationService::class);
    $placementService = mock(InternshipPlacementService::class);
    $journalService = mock(JournalService::class);
    $assessmentService = mock(AssessmentService::class);

    $student = (object) ['id' => 'uuid-1', 'user' => (object) ['name' => 'Test Student']];

    $builder = mock(\Illuminate\Database\Eloquent\Builder::class);
    $registrationService->shouldReceive('query')->andReturn($builder);
    $builder->shouldReceive('with')->andReturnSelf();
    $builder->shouldReceive('limit')->andReturnSelf();
    $builder->shouldReceive('get')->andReturn(collect([$student]));

    $journalService->shouldReceive('getEngagementStats')->andReturn([
        'uuid-1' => ['responsiveness' => 40],
    ]);
    $assessmentService->shouldReceive('getAverageScore')->andReturn([
        'uuid-1' => 60,
    ]);

    $aggregator = new AnalyticsAggregator(
        $registrationService,
        $placementService,
        $journalService,
        $assessmentService,
    );

    app()->setLocale('en');
    $atRisk = $aggregator->getAtRiskStudents(1);

    expect($atRisk)
        ->toHaveCount(1)
        ->and($atRisk[0]['student_name'])
        ->toBe('Test Student')
        ->and($atRisk[0]['risk_level'])
        ->toBe('High');

    // Verify localized reasons (assuming English for test environment)
    expect($atRisk[0]['reason'])
        ->toContain('Low journal verification')
        ->and($atRisk[0]['reason'])
        ->toContain('Low mentor assessment score');
});
