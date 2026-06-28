<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Installation\Actions;

use App\Core\Data\AuditCheck;
use App\Core\Data\AuditReport;
use App\Core\Enums\AuditCategory;
use App\Core\Enums\AuditStatus;
use App\Setup\Installation\Actions\GenerateSetupTokenAction;
use App\Setup\Installation\Actions\InstallSystemAction;
use App\Setup\Installation\Data\SetupTokenData;
use App\Setup\Installation\Services\SystemProvisioner;
use App\SysAdmin\Observability\Services\EnvironmentAuditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use RuntimeException;

uses(RefreshDatabase::class);

test('install system action successfully runs audits, provisions, and returns token', function () {
    $passingCheck = new AuditCheck(
        category: AuditCategory::REQUIREMENTS,
        nameKey: 'php_version',
        status: AuditStatus::PASS,
        messageKey: 'php_version_pass',
    );
    $report = new AuditReport([$passingCheck]);

    $auditorMock = Mockery::mock(EnvironmentAuditor::class);
    $auditorMock->shouldReceive('audit')->once()->andReturn($report);

    $provisionerMock = Mockery::mock(SystemProvisioner::class);
    $provisionerMock->shouldReceive('executeAll')->once()->with(false);

    $generateTokenAction = app(GenerateSetupTokenAction::class);

    $action = new InstallSystemAction($auditorMock, $provisionerMock, $generateTokenAction);
    $result = $action->execute();

    expect($result)->toBeInstanceOf(SetupTokenData::class);
    expect($result->plaintext)->not->toBeEmpty();
    expect($result->expiresAt)->toBeInstanceOf(Carbon::class);
});

test('install system action throws runtime exception if audit fails', function () {
    $failingCheck = new AuditCheck(
        category: AuditCategory::REQUIREMENTS,
        nameKey: 'php_version',
        status: AuditStatus::FAIL,
        messageKey: 'php_version_fail',
    );
    $report = new AuditReport([$failingCheck]);

    $auditorMock = Mockery::mock(EnvironmentAuditor::class);
    $auditorMock->shouldReceive('audit')->once()->andReturn($report);

    $provisionerMock = Mockery::mock(SystemProvisioner::class);
    $provisionerMock->shouldNotReceive('executeAll');

    $generateTokenAction = app(GenerateSetupTokenAction::class);

    $action = new InstallSystemAction($auditorMock, $provisionerMock, $generateTokenAction);

    expect(fn () => $action->execute())->toThrow(
        RuntimeException::class,
        'System audit check failed',
    );
});

test('install system action accepts pre-built report and skips auditor', function () {
    $passingCheck = new AuditCheck(
        category: AuditCategory::REQUIREMENTS,
        nameKey: 'php_version',
        status: AuditStatus::PASS,
        messageKey: 'php_version_pass',
    );
    $report = new AuditReport([$passingCheck]);

    $auditorMock = Mockery::mock(EnvironmentAuditor::class);
    $auditorMock->shouldNotReceive('audit');

    $provisionerMock = Mockery::mock(SystemProvisioner::class);
    $provisionerMock->shouldReceive('executeAll')->once()->with(false);

    $generateTokenAction = app(GenerateSetupTokenAction::class);

    $action = new InstallSystemAction($auditorMock, $provisionerMock, $generateTokenAction);
    $result = $action->execute(report: $report);

    expect($result)->toBeInstanceOf(SetupTokenData::class);
});
