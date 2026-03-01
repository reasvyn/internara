<?php

declare(strict_types=1);

namespace Modules\Report\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Permission\Models\Role;
use Modules\Report\Services\Contracts\ReportGenerator;
use Modules\Shared\Contracts\ExportableDataProvider;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

test('report service can register and list providers', function () {
    $service = app(ReportGenerator::class);

    // Register a mock provider for isolation
    $service->registerProvider(
        new class implements ExportableDataProvider
        {
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

test('it synthesizes a PDF and stores it on the private disk', function () {
    Storage::fake('private');

    $user = User::factory()->create();
    $service = app(ReportGenerator::class);

    // Register a dummy provider
    $service->registerProvider(
        new class implements ExportableDataProvider
        {
            public function getIdentifier(): string
            {
                return 'test_report';
            }

            public function getLabel(): string
            {
                return 'Test Report';
            }

            public function getReportData(array $filters = []): array
            {
                return ['key' => 'value'];
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

    $filePath = $service->generate('test_report', [], $user->id);

    expect($filePath)->toContain('reports/test_report_');

    // Verify physical storage on PRIVATE disk
    Storage::disk('private')->assertExists($filePath);

    // Verify DB record
    $this->assertDatabaseHas('generated_reports', [
        'user_id' => $user->id,
        'file_path' => $filePath,
        'provider_identifier' => 'test_report',
    ]);
});

test('report index component can be rendered', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)->get(route('admin.reports'))->assertOk();
});
