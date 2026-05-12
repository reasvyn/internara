<?php

declare(strict_types=1);

use App\Data\Audit\AuditCheck;
use App\Data\Audit\AuditReport;
use App\Enums\Setup\AuditCategory;
use App\Enums\Shared\AuditStatus;
use App\Models\Setup;
use App\Services\Setup\EnvironmentAuditor;
use App\Support\Setup\SystemProvisioner;

use function Pest\Laravel\artisan;

function passingCheck(AuditCategory $category, string $nameKey, string $messageKey = 'pass', array $nameParams = [], array $messageParams = []): AuditCheck
{
    return new AuditCheck(
        category: $category,
        nameKey: $nameKey,
        status: AuditStatus::Pass,
        messageKey: $messageKey,
        nameParams: $nameParams,
        messageParams: $messageParams,
    );
}

function passingAudit(): AuditReport
{
    return new AuditReport([
        passingCheck(AuditCategory::Requirements, 'php_version', 'pass', ['required' => '8.4.0']),
        passingCheck(AuditCategory::Permissions, 'storage_writable', 'pass'),
        passingCheck(AuditCategory::Database, 'db_connection', 'pass'),
        passingCheck(AuditCategory::Terminal, 'shell_exec', 'pass'),
    ]);
}

function mockProvisioner(): void
{
    $provisioner = Mockery::mock(SystemProvisioner::class);
    $provisioner->shouldReceive('getTasks')->once()->andReturn([
        'ensure_env' => 'Environment file',
        'generate_key' => 'App key',
        'run_migrations' => 'Migrations',
        'run_seeders' => 'Seeders',
        'storage_link' => 'Storage link',
        'clear_cache' => 'Clear cache',
    ]);
    $provisioner->shouldReceive('executeTask')->times(6);

    app()->instance(SystemProvisioner::class, $provisioner);
}

beforeEach(function () {
    Setup::query()->delete();
});

it('installs the system with --force when audit passes', function () {
    $this->mock(EnvironmentAuditor::class)
        ->shouldReceive('audit')->once()->andReturn(passingAudit());

    mockProvisioner();

    artisan('setup:install', ['--force' => true])
        ->expectsOutputToContain(__('setup.cli.force_warning'))
        ->assertSuccessful();

    $setup = Setup::first();
    expect($setup)->not->toBeNull();
    expect($setup->setup_token)->not->toBeNull();
    expect($setup->token_expires_at)->not->toBeNull();
});

it('fails when critical audit checks do not pass', function () {
    $this->mock(EnvironmentAuditor::class)
        ->shouldReceive('audit')->once()->andReturn(new AuditReport([
            new AuditCheck(
                category: AuditCategory::Requirements,
                nameKey: 'php_version',
                status: AuditStatus::Fail,
                messageKey: 'php_version_fail',
                nameParams: ['required' => '8.4.0'],
                messageParams: ['current' => '8.3.0', 'required' => '8.4.0'],
            ),
        ]));

    artisan('setup:install', ['--force' => true])
        ->assertExitCode(1);

    expect(Setup::count())->toBe(0);
});

it('does not create setup record when critical audit fails', function () {
    $this->mock(EnvironmentAuditor::class)
        ->shouldReceive('audit')->once()->andReturn(new AuditReport([
            new AuditCheck(
                category: AuditCategory::Database,
                nameKey: 'db_connection',
                status: AuditStatus::Fail,
                messageKey: 'db_fail',
                nameParams: ['driver' => 'sqlite'],
                messageParams: ['driver' => 'sqlite'],
            ),
        ]));

    artisan('setup:install', ['--force' => true])
        ->assertExitCode(1);

    expect(Setup::count())->toBe(0);
});

it('does not provision when audit fails', function () {
    $this->mock(EnvironmentAuditor::class)
        ->shouldReceive('audit')->once()->andReturn(new AuditReport([
            new AuditCheck(
                category: AuditCategory::Requirements,
                nameKey: 'php_version',
                status: AuditStatus::Fail,
                messageKey: 'php_version_fail',
                nameParams: ['required' => '8.4.0'],
                messageParams: ['current' => '8.3.0', 'required' => '8.4.0'],
            ),
        ]));

    $provisioner = $this->mock(SystemProvisioner::class);
    $provisioner->shouldNotReceive('getTasks');
    $provisioner->shouldNotReceive('executeTask');

    artisan('setup:install', ['--force' => true])
        ->assertExitCode(1);
});

it('proceeds when already installed if --force is used', function () {
    Setup::factory()->installed()->create();

    $this->mock(EnvironmentAuditor::class)
        ->shouldReceive('audit')->once()->andReturn(passingAudit());

    mockProvisioner();

    artisan('setup:install', ['--force' => true])
        ->expectsOutputToContain(__('setup.cli.force_warning'))
        ->assertSuccessful();
});

it('fails when already installed and --force is not used', function () {
    Setup::factory()->installed()->create();

    artisan('setup:install')
        ->expectsOutputToContain(__('setup.cli.already_installed'))
        ->assertExitCode(1);
});

it('fails when --force is used in production environment', function () {
    // Force environment to production
    app()->detectEnvironment(fn () => 'production');

    artisan('setup:install', ['--force' => true])
        ->expectsOutputToContain(__('setup.cli.force_restricted'))
        ->assertExitCode(1);
});

it('fails when already installed via db and --force is not used', function () {
    Setup::factory()->installed()->create();

    artisan('setup:install')
        ->expectsOutputToContain(__('setup.cli.already_installed'))
        ->assertExitCode(1);
});

it('fails when provisioning throws an exception', function () {
    $this->mock(EnvironmentAuditor::class)
        ->shouldReceive('audit')->once()->andReturn(passingAudit());

    $provisioner = $this->mock(SystemProvisioner::class);
    $provisioner->shouldReceive('getTasks')->once()->andReturn([
        'ensure_env' => 'Environment file',
        'run_migrations' => 'Migrations',
    ]);
    $provisioner->shouldReceive('executeTask')->once()->with('ensure_env', true);
    $provisioner->shouldReceive('executeTask')->once()->with('run_migrations', true)
        ->andThrow(new RuntimeException('Migration failed'));

    artisan('setup:install', ['--force' => true])
        ->assertExitCode(1);
});

it('outputs audit results during installation', function () {
    $this->mock(EnvironmentAuditor::class)
        ->shouldReceive('audit')->once()->andReturn(passingAudit());

    mockProvisioner();

    artisan('setup:install', ['--force' => true])
        ->expectsOutputToContain(__('setup.checks.php_version', ['required' => '8.4.0']))
        ->expectsOutputToContain(__('setup.cli.installation_completed'))
        ->assertSuccessful();
});
