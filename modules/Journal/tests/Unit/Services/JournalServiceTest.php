<?php

declare(strict_types=1);

namespace Modules\Journal\Tests\Unit\Services;

use Modules\Journal\Models\JournalEntry;
use Modules\Journal\Services\JournalService;

test('it can query journal entries', function () {
    $entry = mock(JournalEntry::class);
    $service = new JournalService($entry);

    $builder = mock(\Illuminate\Database\Eloquent\Builder::class);
    $entry->shouldReceive('newQuery')->andReturn($builder);
    $builder->shouldReceive('select')->andReturnSelf();

    $result = $service->query();
    expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
});
