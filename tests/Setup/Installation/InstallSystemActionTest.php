<?php

declare(strict_types=1);

use App\Modules\Core\Data\AuditCheck;
use App\Modules\Core\Data\AuditReport;
use App\Modules\Core\Enums\AuditCategory;
use App\Modules\Core\Enums\AuditStatus;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Settings\Services\Settings;
use App\Modules\Setup\Domain\Installation\Actions\GenerateSetupTokenAction;
use App\Modules\Setup\Domain\Installation\Actions\InstallSystemAction;
use App\Modules\Setup\Domain\Installation\Data\SetupTokenData;
use App\Modules\Setup\Domain\Installation\Services\SystemProvisioner;
use App\Modules\SysAdmin\Domain\Observability\Services\EnvironmentAuditor;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| C9ZB6 — Recovery Ecosystem — InstallSystemAction
|--------------------------------------------------------------------------
|
| Spec: docs/specs/C9ZB6-recovery-ecosystem.md (via Setup actions)
| Also covers: 8NZAU — installation pipeline
|
| Tests the orchestration: audit → provision → generate token
| by injecting controlled mocks for auditor, provisioner, and token generator.
|
*/

describe('C9ZB6: InstallSystemAction', function (): void {

    beforeEach(function (): void {
        Settings::clearOverrides();
    });

    /*
    |--------------------------------------------------------------------------
    | Happy path: audit passes, provisioner runs, token generated
    |--------------------------------------------------------------------------
    */

    test('C9ZB6-FR-K1: returns SetupTokenData when audit passes', function (): void {
        $expectedToken = new SetupTokenData(
            plaintext: 'test-token-string',
            expiresAt: now()->addHour(),
        );

        $report = new AuditReport([
            new AuditCheck(
                category: AuditCategory::REQUIREMENTS,
                nameKey: 'php_version',
                status: AuditStatus::PASS,
                messageKey: 'php_version_pass',
            ),
        ]);

        $mockAuditor = Mockery::mock(EnvironmentAuditor::class);
        $mockAuditor->shouldReceive('audit')->once()->andReturn($report);

        $mockProvisioner = Mockery::mock(SystemProvisioner::class);
        $mockProvisioner->shouldReceive('executeAll')->once()->with(false);

        $mockTokenGenerator = Mockery::mock(GenerateSetupTokenAction::class);
        $mockTokenGenerator->shouldReceive('execute')->once()->andReturn($expectedToken);

        $action = new InstallSystemAction($mockAuditor, $mockProvisioner, $mockTokenGenerator);
        $result = $action->execute();

        expect($result)->toBeInstanceOf(SetupTokenData::class);
        expect($result->plaintext)->toBe('test-token-string');
    });

    test('C9ZB6-FR-K1: passes force flag through to provisioner', function (): void {
        $expectedToken = new SetupTokenData(
            plaintext: 'force-token',
            expiresAt: now()->addHour(),
        );

        $report = new AuditReport([
            new AuditCheck(
                category: AuditCategory::REQUIREMENTS,
                nameKey: 'php_version',
                status: AuditStatus::PASS,
                messageKey: 'php_version_pass',
            ),
        ]);

        $mockAuditor = Mockery::mock(EnvironmentAuditor::class);
        $mockAuditor->shouldReceive('audit')->once()->andReturn($report);

        $mockProvisioner = Mockery::mock(SystemProvisioner::class);
        $mockProvisioner->shouldReceive('executeAll')->once()->with(true);

        $mockTokenGenerator = Mockery::mock(GenerateSetupTokenAction::class);
        $mockTokenGenerator->shouldReceive('execute')->once()->andReturn($expectedToken);

        $action = new InstallSystemAction($mockAuditor, $mockProvisioner, $mockTokenGenerator);
        $result = $action->execute(force: true);

        expect($result->plaintext)->toBe('force-token');
    });

    /*
    |--------------------------------------------------------------------------
    | Audit failure: throws RejectedException
    |--------------------------------------------------------------------------
    */

    test('C9ZB6-FR-K1: throws RejectedException when audit fails', function (): void {
        $failedReport = new AuditReport([
            new AuditCheck(
                category: AuditCategory::REQUIREMENTS,
                nameKey: 'php_version',
                status: AuditStatus::FAIL,
                messageKey: 'php_version_fail',
            ),
        ]);

        $mockAuditor = Mockery::mock(EnvironmentAuditor::class);
        $mockAuditor->shouldReceive('audit')->once()->andReturn($failedReport);

        $mockProvisioner = Mockery::mock(SystemProvisioner::class);
        $mockTokenGenerator = Mockery::mock(GenerateSetupTokenAction::class);

        // Provisioner and token generator should NOT be called if audit fails
        $mockProvisioner->shouldNotReceive('executeAll');
        $mockTokenGenerator->shouldNotReceive('execute');

        $action = new InstallSystemAction($mockAuditor, $mockProvisioner, $mockTokenGenerator);

        expect(fn () => $action->execute())
            ->toThrow(RejectedException::class, __('setup.audit_failed'));
    });

    /*
    |--------------------------------------------------------------------------
    | Pre-built report: uses provided report instead of auditing
    |--------------------------------------------------------------------------
    */

    test('C9ZB6-FR-K1: uses pre-built AuditReport when provided as argument', function (): void {
        $expectedToken = new SetupTokenData(
            plaintext: 'prebuilt-report-token',
            expiresAt: now()->addHour(),
        );

        $prebuiltReport = new AuditReport([
            new AuditCheck(
                category: AuditCategory::REQUIREMENTS,
                nameKey: 'php_version',
                status: AuditStatus::PASS,
                messageKey: 'php_version_pass',
            ),
        ]);

        $mockAuditor = Mockery::mock(EnvironmentAuditor::class);
        // Auditor should NOT be called when report is provided
        $mockAuditor->shouldNotReceive('audit');

        $mockProvisioner = Mockery::mock(SystemProvisioner::class);
        $mockProvisioner->shouldReceive('executeAll')->once();

        $mockTokenGenerator = Mockery::mock(GenerateSetupTokenAction::class);
        $mockTokenGenerator->shouldReceive('execute')->once()->andReturn($expectedToken);

        $action = new InstallSystemAction($mockAuditor, $mockProvisioner, $mockTokenGenerator);
        $result = $action->execute(report: $prebuiltReport);

        expect($result->plaintext)->toBe('prebuilt-report-token');
    });

    /*
    |--------------------------------------------------------------------------
    | Audit with only warnings passes (WARN is not FAIL)
    |--------------------------------------------------------------------------
    */

    test('C9ZB6-FR-K1: audit with warnings (no failures) passes through', function (): void {
        $expectedToken = new SetupTokenData(
            plaintext: 'warn-pass-token',
            expiresAt: now()->addHour(),
        );

        $reportWithWarnings = new AuditReport([
            new AuditCheck(
                category: AuditCategory::RECOMMENDATIONS,
                nameKey: 'redis_extension',
                status: AuditStatus::WARN,
                messageKey: 'recommended_fail',
            ),
            new AuditCheck(
                category: AuditCategory::REQUIREMENTS,
                nameKey: 'php_version',
                status: AuditStatus::PASS,
                messageKey: 'php_version_pass',
            ),
        ]);

        $mockAuditor = Mockery::mock(EnvironmentAuditor::class);
        $mockAuditor->shouldReceive('audit')->once()->andReturn($reportWithWarnings);

        $mockProvisioner = Mockery::mock(SystemProvisioner::class);
        $mockProvisioner->shouldReceive('executeAll')->once();

        $mockTokenGenerator = Mockery::mock(GenerateSetupTokenAction::class);
        $mockTokenGenerator->shouldReceive('execute')->once()->andReturn($expectedToken);

        $action = new InstallSystemAction($mockAuditor, $mockProvisioner, $mockTokenGenerator);
        $result = $action->execute();

        expect($result->plaintext)->toBe('warn-pass-token');
    });
});
