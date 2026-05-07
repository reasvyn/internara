<?php

declare(strict_types=1);

use App\Actions\Setup\ProvisionSystemAction;
use App\Enums\Setup\AuditCategory;
use App\Enums\Shared\AuditStatus;
use App\Models\Setup;
use App\Services\Setup\EnvironmentAuditor;

use function Pest\Laravel\artisan;

function passingCheck(AuditCategory $category, string $nameKey, string $messageKey = 'pass', array $nameParams = [], array $messageParams = []): array
{
    return [
        'category' => $category,
        'nameKey' => $nameKey,
        'status' => AuditStatus::Pass,
        'messageKey' => $messageKey,
        'nameParams' => $nameParams,
        'messageParams' => $messageParams,
    ];
}

it('installs the system with --force when audit passes', function () {
    $auditor = $this->mock(EnvironmentAuditor::class);
    $auditor->shouldReceive('audit')->once()->andReturn([
        passingCheck(AuditCategory::Requirements, 'php_version', 'php_version_pass', ['required' => '8.4.0'], ['current' => PHP_VERSION, 'required' => '8.4.0']),
        passingCheck(AuditCategory::Permissions, 'writable_dir', 'writable_pass', ['directory' => 'storage'], ['directory' => 'storage']),
        passingCheck(AuditCategory::Permissions, 'writable_dir', 'writable_pass', ['directory' => 'bootstrap/cache'], ['directory' => 'bootstrap/cache']),
        passingCheck(AuditCategory::Database, 'db_connection', 'db_pass', ['driver' => 'sqlite'], ['driver' => 'sqlite']),
        passingCheck(AuditCategory::Terminal, 'terminal_animations', 'terminal_animations_pass'),
        passingCheck(AuditCategory::Terminal, 'terminal_interactive', 'terminal_interactive_pass'),
    ]);

    $provisioner = $this->mock(ProvisionSystemAction::class);
    $provisioner->shouldReceive('getTasks')->once()->andReturn([
        'ensure_env' => 'Environment file',
        'generate_key' => 'App key',
        'run_migrations' => 'Migrations',
        'run_seeders' => 'Seeders',
        'storage_link' => 'Storage link',
        'clear_cache' => 'Cache',
    ]);
    $provisioner->shouldReceive('executeTask')->times(6);

    artisan('setup:install', ['--force' => true])
        ->expectsOutputToContain(__('setup.cli.force_warning'))
        ->assertSuccessful();

    $setup = Setup::first();
    expect($setup)->not->toBeNull();
    expect($setup->setup_token)->not->toBeNull();
    expect($setup->token_expires_at)->not->toBeNull();
});

it('fails when critical audit checks do not pass', function () {
    $auditor = $this->mock(EnvironmentAuditor::class);
    $auditor->shouldReceive('audit')->once()->andReturn([
        [
            'category' => AuditCategory::Requirements,
            'nameKey' => 'php_version',
            'status' => AuditStatus::Fail,
            'messageKey' => 'php_version_fail',
            'nameParams' => ['required' => '8.4.0'],
            'messageParams' => ['current' => '8.3.0', 'required' => '8.4.0'],
        ],
    ]);

    artisan('setup:install', ['--force' => true])
        ->assertExitCode(1);

    expect(Setup::count())->toBe(0);
});

it('does not create setup record when critical audit fails', function () {
    $auditor = $this->mock(EnvironmentAuditor::class);
    $auditor->shouldReceive('audit')->once()->andReturn([
        [
            'category' => AuditCategory::Database,
            'nameKey' => 'db_connection',
            'status' => AuditStatus::Fail,
            'messageKey' => 'db_fail',
            'nameParams' => ['driver' => 'sqlite'],
            'messageParams' => ['driver' => 'sqlite'],
        ],
    ]);

    artisan('setup:install', ['--force' => true])
        ->assertExitCode(1);

    expect(Setup::count())->toBe(0);
});
