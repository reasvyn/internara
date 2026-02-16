<?php

declare(strict_types=1);

namespace Modules\Journal\Tests\Unit\Services;

use Modules\Assessment\Services\Contracts\CompetencyService;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\Journal\Models\JournalEntry;
use Modules\Journal\Services\JournalService;

test('it can query journal entries', function () {
    $registrationService = mock(RegistrationService::class);
    $competencyService = mock(CompetencyService::class);
    $entry = mock(JournalEntry::class);
    $service = new JournalService($registrationService, $competencyService, $entry);

    $builder = mock(\Illuminate\Database\Eloquent\Builder::class);
    $entry->shouldReceive('newQuery')->andReturn($builder);
    $builder->shouldReceive('select')->andReturnSelf();

    $result = $service->query();
    expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
});
