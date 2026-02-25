<?php

declare(strict_types=1);

namespace Modules\Journal\Tests\Unit\Services;

use Illuminate\Support\Facades\Gate;
use Modules\Assessment\Services\Contracts\CompetencyService;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\Journal\Models\JournalEntry;
use Modules\Journal\Services\JournalService;

describe('Journal Service', function () {
    beforeEach(function () {
        $this->registrationService = mock(RegistrationService::class);
        $this->competencyService = mock(CompetencyService::class);
        $this->model = mock(JournalEntry::class);
        $this->service = new JournalService(
            $this->registrationService,
            $this->competencyService,
            $this->model,
        );
    });

    test('it enforces authorization for journal creation [SYRS-NF-502]', function () {
        Gate::shouldReceive('authorize')
            ->once()
            ->with('create', JournalEntry::class)
            ->andThrow(\Illuminate\Auth\Access\AuthorizationException::class);

        $this->service->create(['registration_id' => 'some-uuid']);
    })->throws(\Illuminate\Auth\Access\AuthorizationException::class);
});
