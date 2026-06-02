<?php

declare(strict_types=1);

namespace Tests\Feature\Setup\Console;

use App\Domain\Core\Data\AuditCheck;
use App\Domain\Core\Data\AuditReport;
use App\Domain\Core\Enums\AuditCategory;
use App\Domain\Core\Enums\AuditStatus;
use App\Domain\Setup\Models\Setup;
use App\Domain\Setup\Services\EnvironmentAuditor;
use App\Domain\Setup\Support\SystemProvisioner;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('SetupInstallCommand', function () {
    it('rejects when already installed without --force', function () {
        $this->artisan('setup:install')
            ->assertFailed();
    });

    it('shows already installed message', function () {
        $this->artisan('setup:install')
            ->expectsOutputToContain(__('setup.cli.already_installed'));
    });

    it('suggests health check when installed', function () {
        $this->artisan('setup:install')
            ->expectsOutputToContain(__('setup.cli.try_health_check'));
    });

    it('proceeds with --force when installed', function () {
        Setup::query()->delete();

        $auditor = \Mockery::mock(EnvironmentAuditor::class);
        $auditor->shouldReceive('audit')->andReturn(new AuditReport([
            new AuditCheck(AuditCategory::REQUIREMENTS, 'php_version', AuditStatus::PASS, 'php_version_pass', ['required' => '8.4.0'], ['current' => '8.4.21']),
        ]));
        $this->app->instance(EnvironmentAuditor::class, $auditor);

        $provisioner = \Mockery::mock(SystemProvisioner::class);
        $provisioner->shouldReceive('getTasks')->andReturn([]);
        $this->app->instance(SystemProvisioner::class, $provisioner);

        $this->artisan('setup:install --force')
            ->assertSuccessful();
    });

    it('shows force warning with --force', function () {
        Setup::query()->delete();

        $auditor = \Mockery::mock(EnvironmentAuditor::class);
        $auditor->shouldReceive('audit')->andReturn(new AuditReport([
            new AuditCheck(AuditCategory::REQUIREMENTS, 'php_version', AuditStatus::PASS, 'php_version_pass', ['required' => '8.4.0'], ['current' => '8.4.21']),
        ]));
        $this->app->instance(EnvironmentAuditor::class, $auditor);

        $provisioner = \Mockery::mock(SystemProvisioner::class);
        $provisioner->shouldReceive('getTasks')->andReturn([]);
        $this->app->instance(SystemProvisioner::class, $provisioner);

        $this->artisan('setup:install --force')
            ->expectsOutputToContain(__('setup.cli.force_warning'));
    });

    it('rejects force in non-allowed environment', function () {
        config()->set('setup.force_allowed_environments', []);

        $this->artisan('setup:install --force')
            ->assertFailed()
            ->expectsOutputToContain(__('setup.cli.force_restricted'));
    });

    it('aborts when critical audit checks fail', function () {
        Setup::query()->delete();

        $auditor = \Mockery::mock(EnvironmentAuditor::class);
        $auditor->shouldReceive('audit')->andReturn(new AuditReport([
            new AuditCheck(AuditCategory::REQUIREMENTS, 'php_version', AuditStatus::FAIL, 'php_version_fail', ['required' => '9.0.0'], ['current' => '8.4.21']),
        ]));
        $this->app->instance(EnvironmentAuditor::class, $auditor);

        $this->artisan('setup:install --force')
            ->assertFailed()
            ->expectsOutputToContain(__('setup.cli.audit_failed'));
    });

    it('passes with --check-only when audit passes', function () {
        Setup::query()->delete();

        $auditor = \Mockery::mock(EnvironmentAuditor::class);
        $auditor->shouldReceive('audit')->andReturn(new AuditReport([
            new AuditCheck(AuditCategory::REQUIREMENTS, 'php_version', AuditStatus::PASS, 'php_version_pass', ['required' => '8.4.0'], ['current' => '8.4.21']),
        ]));
        $this->app->instance(EnvironmentAuditor::class, $auditor);

        $this->artisan('setup:install --check-only')
            ->assertSuccessful()
            ->expectsOutputToContain(__('setup.cli.check_only_complete'));
    });

    it('does not provision with --check-only', function () {
        Setup::query()->delete();

        $auditor = \Mockery::mock(EnvironmentAuditor::class);
        $auditor->shouldReceive('audit')->andReturn(new AuditReport([
            new AuditCheck(AuditCategory::REQUIREMENTS, 'php_version', AuditStatus::PASS, 'php_version_pass', ['required' => '8.4.0'], ['current' => '8.4.21']),
        ]));
        $this->app->instance(EnvironmentAuditor::class, $auditor);

        $provisioner = \Mockery::mock(SystemProvisioner::class);
        $provisioner->shouldNotReceive('executeTask');
        $this->app->instance(SystemProvisioner::class, $provisioner);

        $this->artisan('setup:install --check-only')
            ->assertSuccessful();
    });

    it('runs provisioning tasks', function () {
        Setup::query()->delete();

        $auditor = \Mockery::mock(EnvironmentAuditor::class);
        $auditor->shouldReceive('audit')->andReturn(new AuditReport([
            new AuditCheck(AuditCategory::REQUIREMENTS, 'php_version', AuditStatus::PASS, 'php_version_pass', ['required' => '8.4.0'], ['current' => '8.4.21']),
        ]));
        $this->app->instance(EnvironmentAuditor::class, $auditor);

        $provisioner = \Mockery::mock(SystemProvisioner::class);
        $provisioner->shouldReceive('getTasks')->andReturn([
            'ensure_env' => 'Ensuring env file',
            'generate_key' => 'Generating app key',
        ]);
        $provisioner->shouldReceive('executeTask')->twice();
        $this->app->instance(SystemProvisioner::class, $provisioner);

        $this->artisan('setup:install --force')
            ->assertSuccessful();
    });

    it('generates setup token on success', function () {
        Setup::query()->delete();

        $auditor = \Mockery::mock(EnvironmentAuditor::class);
        $auditor->shouldReceive('audit')->andReturn(new AuditReport([
            new AuditCheck(AuditCategory::REQUIREMENTS, 'php_version', AuditStatus::PASS, 'php_version_pass', ['required' => '8.4.0'], ['current' => '8.4.21']),
        ]));
        $this->app->instance(EnvironmentAuditor::class, $auditor);

        $provisioner = \Mockery::mock(SystemProvisioner::class);
        $provisioner->shouldReceive('getTasks')->andReturn([]);
        $this->app->instance(SystemProvisioner::class, $provisioner);

        $this->artisan('setup:install --force')
            ->assertSuccessful();

        $setup = Setup::first();
        expect($setup->setup_token)->not->toBeNull();
    });

    it('displays quick access URL on success', function () {
        Setup::query()->delete();

        $auditor = \Mockery::mock(EnvironmentAuditor::class);
        $auditor->shouldReceive('audit')->andReturn(new AuditReport([
            new AuditCheck(AuditCategory::REQUIREMENTS, 'php_version', AuditStatus::PASS, 'php_version_pass', ['required' => '8.4.0'], ['current' => '8.4.21']),
        ]));
        $this->app->instance(EnvironmentAuditor::class, $auditor);

        $provisioner = \Mockery::mock(SystemProvisioner::class);
        $provisioner->shouldReceive('getTasks')->andReturn([]);
        $this->app->instance(SystemProvisioner::class, $provisioner);

        $this->artisan('setup:install --force')
            ->expectsOutputToContain(__('setup.cli.quick_access'));
    });

    it('displays manual entry code on success', function () {
        Setup::query()->delete();

        $auditor = \Mockery::mock(EnvironmentAuditor::class);
        $auditor->shouldReceive('audit')->andReturn(new AuditReport([
            new AuditCheck(AuditCategory::REQUIREMENTS, 'php_version', AuditStatus::PASS, 'php_version_pass', ['required' => '8.4.0'], ['current' => '8.4.21']),
        ]));
        $this->app->instance(EnvironmentAuditor::class, $auditor);

        $provisioner = \Mockery::mock(SystemProvisioner::class);
        $provisioner->shouldReceive('getTasks')->andReturn([]);
        $this->app->instance(SystemProvisioner::class, $provisioner);

        $this->artisan('setup:install --force')
            ->expectsOutputToContain(__('setup.cli.manual_entry'));
    });

    it('handles exceptions gracefully', function () {
        Setup::query()->delete();

        $auditor = \Mockery::mock(EnvironmentAuditor::class);
        $auditor->shouldReceive('audit')->andThrow(new \RuntimeException('Connection refused'));
        $this->app->instance(EnvironmentAuditor::class, $auditor);

        $this->artisan('setup:install --force')
            ->assertFailed();
    });

    it('displays banner on start', function () {
        $this->artisan('setup:install')
            ->expectsOutputToContain(__('setup.cli.banner_title'));
    });
});
