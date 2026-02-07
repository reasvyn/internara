<?php

declare(strict_types=1);

namespace Modules\Guidance\Tests\Unit\Services;

use Modules\Guidance\Models\Handbook;
use Modules\Guidance\Services\HandbookService;

test('it can query handbooks', function () {
    $handbook = mock(Handbook::class);
    $service = new HandbookService($handbook);

    $builder = mock(\Illuminate\Database\Eloquent\Builder::class);
    $handbook->shouldReceive('newQuery')->andReturn($builder);
    $builder->shouldReceive('select')->andReturnSelf();

    $result = $service->query();
    expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
});
