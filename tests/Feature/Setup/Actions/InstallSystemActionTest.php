<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Actions;

use App\Domain\Core\Data\AuditCheck;
use App\Domain\Core\Data\AuditReport;
use App\Domain\Core\Enums\AuditCategory;
use App\Domain\Core\Enums\AuditStatus;
use App\Domain\Setup\Actions\GenerateSetupTokenAction;
use App\Domain\Setup\Actions\InstallSystemAction;
use App\Domain\Setup\Services\EnvironmentAuditor;
use App\Domain\Setup\Support\SystemProvisioner;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery;
use RuntimeException;

uses(LazilyRefreshDatabase::class);

describe('InstallSystemAction', function () {
    it('throws if audit report has failures', function () {
        $action = app(InstallSystemAction::class);

        $report = new AuditReport([
            new AuditCheck(
                category: AuditCategory::REQUIREMENTS,
                nameKey: 'php_version',
                status: AuditStatus::FAIL,
                messageKey: 'php_version_fail',
            ),
        ]);

        $action->execute(report: $report);
    })->throws(RuntimeException::class, 'System audit check failed.');

    it('throws if audit is run and fails', function () {
        $auditor = Mockery::mock(EnvironmentAuditor::class);
        $auditor->shouldReceive('audit')->once()->andReturn(
            new AuditReport([
                new AuditCheck(
                    category: AuditCategory::REQUIREMENTS,
                    nameKey: 'php_version',
                    status: AuditStatus::FAIL,
                    messageKey: 'php_version_fail',
                ),
            ])
        );

        $action = new InstallSystemAction(
            auditor: $auditor,
            provisioner: app(SystemProvisioner::class),
            generateToken: app(GenerateSetupTokenAction::class),
        );

        $action->execute();
    })->throws(RuntimeException::class, 'System audit check failed.');

    it('passes force flag to provisioner', function () {
        $auditor = Mockery::mock(EnvironmentAuditor::class);
        $auditor->shouldReceive('audit')->once()->andReturn(
            new AuditReport([
                new AuditCheck(
                    category: AuditCategory::REQUIREMENTS,
                    nameKey: 'php_version',
                    status: AuditStatus::PASS,
                    messageKey: 'php_version_pass',
                ),
            ])
        );

        $provisioner = Mockery::mock(SystemProvisioner::class);
        $provisioner->shouldReceive('executeAll')->once()->with(true)->andReturnNull();

        $action = new InstallSystemAction(
            auditor: $auditor,
            provisioner: $provisioner,
            generateToken: app(GenerateSetupTokenAction::class),
        );

        $result = $action->execute(force: true);

        expect($result)->toHaveKeys(['plaintext', 'expires_at']);
    });
});
