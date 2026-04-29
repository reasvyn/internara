<?php

declare(strict_types=1);

namespace Modules\Internship\Tests\Unit\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Modules\Internship\Models\Company;
use Modules\Internship\Services\Contracts\CompanyService;

describe('Company Service', function () {
    test('it enforces authorization for company creation [SYRS-NF-502]', function () {
        Gate::shouldReceive('authorize')
            ->once()
            ->with('create', Company::class)
            ->andThrow(AuthorizationException::class);

        $service = app(CompanyService::class);
        $service->create(['name' => 'Unauthorized Industry']);
    })->throws(AuthorizationException::class);
});
