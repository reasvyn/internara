<?php

declare(strict_types=1);

namespace Modules\Report\Tests\Unit\Services;

use Modules\Report\Models\GeneratedReport;
use Modules\Report\Services\GeneratedReportService;

test('it can query generated reports', function () {
    $report = mock(GeneratedReport::class);
    $service = new GeneratedReportService($report);

    $builder = mock(\Illuminate\Database\Eloquent\Builder::class);
    $report->shouldReceive('newQuery')->andReturn($builder);
    $builder->shouldReceive('select')->andReturnSelf();
    $builder->shouldReceive('with')->andReturnSelf();

    $result = $service->query();
    expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
});
