<?php

declare(strict_types=1);

namespace Modules\Mentor\Tests\Unit\Services;

use Modules\Mentor\Models\MentoringVisit;
use Modules\Mentor\Services\MentoringService;

test('it can query mentoring visits', function () {
    $visit = mock(MentoringVisit::class);
    $service = new MentoringService($visit);

    $builder = mock(\Illuminate\Database\Eloquent\Builder::class);
    $visit->shouldReceive('newQuery')->andReturn($builder);
    $builder->shouldReceive('select')->andReturnSelf();
    $builder->shouldReceive('with')->andReturnSelf();

    $result = $service->query();
    expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
});
