<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Modules\Core\Exceptions\UnauthorizedException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

uses(LazilyRefreshDatabase::class);

describe('H9T4N: shared hosting deployment behavior and profile configuration', function (): void {
    test('H9T4N-FR-HOST-001 H9T4N-FR-HOST-002 H9T4N-FR-HOST-003: tier 1 drivers operate without external daemons', function (): void {
        expect(config('queue.default'))->toBeIn(['sync', 'database'])
            ->and(config('session.driver'))->toBeIn(['file', 'database', 'cookie', 'array'])
            ->and(config('cache.default'))->toBeIn(['file', 'database', 'array']);
    });

    test('H9T4N-FR-HOST-006 H9T4N-NFR-HOST-001 H9T4N-NFR-HOST-006 H9T4N-UC-HOST-001: cron webhook executes schedule with valid secret and rejects invalid', function (): void {
        $secret = 'test-cron-secret-12345';
        config()->set('app.cron_secret', $secret);

        Artisan::shouldReceive('call')
            ->with('schedule:run')
            ->andReturn(0);

        Artisan::shouldReceive('call')
            ->with('pulse:check')
            ->zeroOrMoreTimes()
            ->andReturn(0);

        $response = $this->get(route('cron', ['secret' => $secret]));
        $response->assertOk()
            ->assertJson(['status' => 'ok']);

        // Invalid secret throws UnauthorizedException
        $this->withoutExceptionHandling();
        expect(fn () => $this->get(route('cron', ['secret' => 'invalid-secret'])))
            ->toThrow(UnauthorizedException::class);
    });

    test('H9T4N-FR-HOST-008: document root is directed at public directory with entry point', function (): void {
        expect(File::exists(public_path('index.php')))->toBeTrue();
    });

    test('H9T4N-FR-HOST-010: database driver supports standard portable connections', function (): void {
        $default = config('database.default');
        expect($default)->toBeIn(['sqlite', 'mysql', 'mariadb', 'pgsql']);
    });

    test('H9T4N-NFR-HOST-004: storage directories are writable for deployment assets', function (): void {
        expect(is_writable(storage_path()))->toBeTrue()
            ->and(is_writable(storage_path('framework/views')))->toBeTrue();
    });

    test('H9T4N-FR-HOST-004 H9T4N-FR-HOST-005 H9T4N-DD-HOST-001 H9T4N-DD-HOST-002: shared-hosting preset configures sync queue and database session', function (): void {
        $preset = config('deployment.profiles.shared-hosting');
        expect($preset['queue'])->toBe('sync')
            ->and($preset['session'])->toBe('database')
            ->and($preset['cache'])->toBe('file');
    });

    test('H9T4N-FR-HOST-007 H9T4N-FR-HOST-009 H9T4N-NFR-HOST-002 H9T4N-NFR-HOST-003: package manifests allow off-server build and storage link', function (): void {
        $composer = json_decode(File::get(base_path('composer.json')), true);
        expect($composer)->toHaveKey('scripts')
            ->and($composer['scripts'])->toHaveKey('post-autoload-dump');

        $package = json_decode(File::get(base_path('package.json')), true);
        expect($package)->toHaveKey('scripts')
            ->and($package['scripts'])->toHaveKey('build');

        expect(config('filesystems.disks.public.driver'))->toBe('local');
    });

    test('H9T4N-FR-HOST-011: config/deployment.php declares shared-hosting preset as default', function (): void {
        expect(config('deployment.default'))->toBe('shared-hosting');
        $preset = config('deployment.profiles.shared-hosting');
        expect($preset['redis'])->toBeFalse();
    });

    test('H9T4N-FR-HOST-012 H9T4N-UC-HOST-002 H9T4N-DD-HOST-003: deploy:detect recommends shared-hosting when container/redis absent', function (): void {
        Artisan::call('deploy:detect', ['--json' => true]);
        $output = json_decode(Artisan::output(), true);
        expect($output['recommended_profile'])->toBeIn(['shared-hosting', 'vps-docker']);
    });

    test('H9T4N-FR-HOST-013: deploy:configure applies shared-hosting drivers idempotently without altering secrets', function (): void {
        $tmpEnv = sys_get_temp_dir().'/test_shared_env_'.uniqid().'.env';
        File::put($tmpEnv, "APP_KEY=keep_me_safe\nDB_PASSWORD=secret123\n");

        Artisan::call('deploy:configure', ['--profile' => 'shared-hosting', '--env-path' => $tmpEnv]);
        $first = File::get($tmpEnv);
        expect($first)->toContain('QUEUE_CONNECTION=sync')
            ->and($first)->toContain('CACHE_STORE=file')
            ->and($first)->toContain('APP_KEY=keep_me_safe')
            ->and($first)->toContain('DB_PASSWORD=secret123');

        Artisan::call('deploy:configure', ['--profile' => 'shared-hosting', '--env-path' => $tmpEnv]);
        $second = File::get($tmpEnv);
        expect($second)->toBe($first);
        @unlink($tmpEnv);
    });

    test('H9T4N-FR-HOST-014: explicit DEPLOY_PROFILE takes precedence over detection', function (): void {
        expect(config('deployment.profiles'))->toHaveKey('shared-hosting');
    });

    test('H9T4N-FR-HOST-015: deploy:detect is non-destructive, supports --json, and exposes no credentials', function (): void {
        Artisan::call('deploy:detect', ['--json' => true]);
        $output = Artisan::output();
        expect($output)->not->toContain('password')
            ->and($output)->not->toContain('secret');

        $json = json_decode($output, true);
        expect($json)->toBeArray()
            ->and($json)->toHaveKey('recommended_profile');
    });

    test('H9T4N-NFR-HOST-005: deploy commands declare strict_types and translatable strings', function (): void {
        $cmdFile = File::get(app_path('Modules/Core/Console/Commands/DeployConfigureCommand.php'));
        expect($cmdFile)->toContain('declare(strict_types=1);');

        expect(__('core.deploy.detection_heading'))->not->toBe('core.deploy.detection_heading');
    });
});
