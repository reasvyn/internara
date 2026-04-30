<?php

declare(strict_types=1);

namespace Modules\Guidance\Tests\Unit\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Guidance\Models\Handbook;
use Modules\Guidance\Services\HandbookService;

test('it can query handbooks', function () {
    $handbook = mock(Handbook::class);
    $service = new HandbookService($handbook);

    $builder = mock(Builder::class);
    $handbook->shouldReceive('newQuery')->andReturn($builder);
    $builder->shouldReceive('select')->andReturnSelf();
    $builder->shouldReceive('with')->andReturnSelf();

    $result = $service->query();
    expect($result)->toBeInstanceOf(Builder::class);
});
