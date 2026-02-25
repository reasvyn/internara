<?php

declare(strict_types=1);

namespace Modules\Internship\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Modules\Internship\Models\Company;
use Modules\Internship\Services\Contracts\CompanyService;

uses(RefreshDatabase::class);

describe('Company Service', function () {
    test('it enforces authorization for company creation [SYRS-NF-502]', function () {
        Gate::shouldReceive('authorize')
            ->once()
            ->with('create', Company::class)
            ->andThrow(\Illuminate\Auth\Access\AuthorizationException::class);

        $service = app(CompanyService::class);
        $service->create(['name' => 'Unauthorized Industry']);
    })->throws(\Illuminate\Auth\Access\AuthorizationException::class);
});
