<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Actions;

use App\Data\AuditCheck;
use App\Data\AuditReport;
use App\Enums\AuditCategory;
use App\Enums\AuditStatus;
use App\Setup\Actions\GenerateSetupTokenAction;
use App\Setup\Actions\InstallSystemAction;
use App\Setup\Support\SystemProvisioner;
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

    expect($result)->toHaveKeys(['plaintext', 'expires_at']);
    expect($result['plaintext'])->not->toBeEmpty();
    expect($result['expires_at'])->toBeInstanceOf(Carbon::class);
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

    expect(fn () => $action->execute())->toThrow(RuntimeException::class, 'System audit check failed');
});
