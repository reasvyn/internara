<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Report\Services\Contracts\ReportGenerator;
use Modules\Shared\Contracts\ExportableDataProvider;


test('report service can register and list providers', function () {
    $service = app(ReportGenerator::class);

    // Register a mock provider for isolation
    $service->registerProvider(
        new class implements ExportableDataProvider {
            public function getIdentifier(): string
            {
                return 'test';
            }

            public function getLabel(): string
            {
                return 'Test';
            }

            public function getReportData(array $filters = []): array
            {
                return [];
            }

            public function getTemplate(): string
            {
                return 'report::templates.generic';
            }

            public function getFilterRules(): array
            {
                return [];
            }
        },
    );

    $providers = $service->getProviders();
    expect($providers)->not->toBeEmpty();
});

test('report index component can be rendered', function () {
    \Modules\Permission\Models\Role::create(['name' => 'admin', 'guard_name' => 'web']);

    $this->actingAs(createAdmin())->get(route('admin.reports'))->assertOk();
});

function createAdmin()
{
    $user = \Modules\User\Models\User::factory()->create();
    $user->assignRole('admin');

    return $user;
}
