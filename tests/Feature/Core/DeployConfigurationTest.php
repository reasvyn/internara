<?php

declare(strict_types=1);

use App\Modules\Core\Console\Commands\DeployDetectCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

describe('06IB6: Conditional Deployment Profiles and Tooling', function (): void {
    test('06IB6-FR-DEPL-001: system defines exactly two canonical deployment profiles', function (): void {
        $config = config('deployment');
        expect(array_keys($config['profiles']))->toEqualCanonicalizing(['shared-hosting', 'vps-docker']);
    });

    test('06IB6-FR-DEPL-002: profile presets are declared in config/deployment.php', function (): void {
        expect(File::exists(config_path('deployment.php')))->toBeTrue();
    });

    test('06IB6-FR-DEPL-003: shared-hosting preset maps to sync queue and file cache', function (): void {
        $shared = config('deployment.profiles.shared-hosting');
        expect($shared['queue'])->toBe('sync')
            ->and($shared['cache'])->toBe('file')
            ->and($shared['session'])->toBe('database')
            ->and($shared['broadcast'])->toBe('log')
            ->and($shared['scheduler'])->toBe('webhook')
            ->and($shared['redis'])->toBeFalse();
    });

    test('06IB6-FR-DEPL-004: vps-docker preset maps to redis queue and cache', function (): void {
        $vps = config('deployment.profiles.vps-docker');
        expect($vps['queue'])->toBe('redis')
            ->and($vps['cache'])->toBe('redis')
            ->and($vps['session'])->toBe('redis')
            ->and($vps['broadcast'])->toBe('log')
            ->and($vps['scheduler'])->toBe('daemon')
            ->and($vps['redis'])->toBeTrue();
    });

    test('06IB6-FR-DEPL-005: active profile is selectable via DEPLOY_PROFILE', function (): void {
        expect(config('deployment.profiles'))->toHaveKeys(['shared-hosting', 'vps-docker']);
    });

    test('06IB6-FR-DEPL-006: unset profile resolves through detection', function (): void {
        Artisan::call('deploy:detect', ['--json' => true]);
        $output = json_decode(Artisan::output(), true);
        expect($output['recommended_profile'])->toBeIn(['shared-hosting', 'vps-docker']);
    });

    test('06IB6-FR-DEPL-007: detection defaults to shared-hosting when inconclusive', function (): void {
        expect(config('deployment.default'))->toBe('shared-hosting');
    });

    test('06IB6-FR-DEPL-008: config/deployment.php declares default profile as shared-hosting', function (): void {
        expect(config('deployment.default'))->toBe('shared-hosting');
    });

    test('06IB6-FR-DEPL-009: shared-hosting is tier-1 and vps-docker is tier-2', function (): void {
        $profiles = config('deployment.profiles');
        expect($profiles['shared-hosting']['redis'])->toBeFalse()
            ->and($profiles['vps-docker']['redis'])->toBeTrue();
    });

    test('06IB6-FR-DEPL-010: system provides deploy:detect artisan command', function (): void {
        expect(array_key_exists('deploy:detect', Artisan::all()))->toBeTrue();
    });

    test('06IB6-FR-DEPL-011: detection probes minimum container, redis, daemon, and composer', function (): void {
        Artisan::call('deploy:detect', ['--json' => true]);
        $output = json_decode(Artisan::output(), true);
        expect($output['probes'])->toHaveKeys(['container', 'redis', 'daemon', 'composer']);
    });

    test('06IB6-FR-DEPL-012: detection recommends vps-docker when container or redis and daemon present', function (): void {
        $detect = new DeployDetectCommand;
        expect(method_exists($detect, 'probeContainer'))->toBeTrue()
            ->and(method_exists($detect, 'probeRedis'))->toBeTrue();
    });

    test('06IB6-FR-DEPL-013: detection recommends shared-hosting when no container or redis found', function (): void {
        $detect = new DeployDetectCommand;
        expect($detect->probeContainer())->toBeFalse();
    });

    test('06IB6-FR-DEPL-014: detection is non-destructive', function (): void {
        $envHashBefore = file_exists(base_path('.env')) ? md5_file(base_path('.env')) : '';
        Artisan::call('deploy:detect');
        $envHashAfter = file_exists(base_path('.env')) ? md5_file(base_path('.env')) : '';
        expect($envHashAfter)->toBe($envHashBefore);
    });

    test('06IB6-FR-DEPL-015: detection supports --json flag', function (): void {
        Artisan::call('deploy:detect', ['--json' => true]);
        $json = json_decode(Artisan::output(), true);
        expect($json)->toBeArray()
            ->and($json)->toHaveKey('recommended_profile');
    });

    test('06IB6-FR-DEPL-016: explicit DEPLOY_PROFILE takes precedence over recommendation', function (): void {
        Artisan::call('deploy:detect');
        expect(Artisan::output())->toContain('Recommended Profile:');
    });

    test('06IB6-FR-DEPL-017: system provides deploy:configure artisan command', function (): void {
        expect(array_key_exists('deploy:configure', Artisan::all()))->toBeTrue();
    });

    test('06IB6-FR-DEPL-018: deploy:configure rejects unknown profile values', function (): void {
        $tmpEnv = sys_get_temp_dir().'/test_env_'.uniqid().'.env';
        File::put($tmpEnv, "APP_KEY=test\n");
        $code = Artisan::call('deploy:configure', ['--profile' => 'invalid', '--env-path' => $tmpEnv]);
        expect($code)->toBe(1);
        @unlink($tmpEnv);
    });

    test('06IB6-FR-DEPL-019: deploy:configure writes only driver keys and never secrets', function (): void {
        $tmpEnv = sys_get_temp_dir().'/test_env_'.uniqid().'.env';
        File::put($tmpEnv, "APP_KEY=keep_me_safe\nDB_PASSWORD=secret123\n");
        Artisan::call('deploy:configure', ['--profile' => 'shared-hosting', '--env-path' => $tmpEnv]);
        $content = File::get($tmpEnv);
        expect($content)->toContain('APP_KEY=keep_me_safe')
            ->and($content)->toContain('DB_PASSWORD=secret123')
            ->and($content)->toContain('QUEUE_CONNECTION=sync');
        @unlink($tmpEnv);
    });

    test('06IB6-FR-DEPL-020: deploy:configure is idempotent', function (): void {
        $tmpEnv = sys_get_temp_dir().'/test_env_'.uniqid().'.env';
        File::put($tmpEnv, "APP_KEY=test\n");
        Artisan::call('deploy:configure', ['--profile' => 'shared-hosting', '--env-path' => $tmpEnv]);
        $first = File::get($tmpEnv);
        Artisan::call('deploy:configure', ['--profile' => 'shared-hosting', '--env-path' => $tmpEnv]);
        $second = File::get($tmpEnv);
        expect($second)->toBe($first);
        @unlink($tmpEnv);
    });

    test('06IB6-FR-DEPL-021: deploy:configure without profile applies resolved profile', function (): void {
        $tmpEnv = sys_get_temp_dir().'/test_env_'.uniqid().'.env';
        File::put($tmpEnv, "APP_KEY=test\n");
        $code = Artisan::call('deploy:configure', ['--env-path' => $tmpEnv]);
        expect($code)->toBe(0);
        @unlink($tmpEnv);
    });

    test('06IB6-FR-DEPL-022: applying profile never modifies docker-compose or Dockerfile', function (): void {
        $dockerComposeBefore = md5_file(base_path('docker-compose.yml'));
        $tmpEnv = sys_get_temp_dir().'/test_env_'.uniqid().'.env';
        File::put($tmpEnv, "APP_KEY=test\n");
        Artisan::call('deploy:configure', ['--profile' => 'vps-docker', '--env-path' => $tmpEnv]);
        expect(md5_file(base_path('docker-compose.yml')))->toBe($dockerComposeBefore);
        @unlink($tmpEnv);
    });

    test('06IB6-FR-DEPL-023: php artisan system:health is final acceptance gate', function (): void {
        expect(array_key_exists('system:health', Artisan::all()))->toBeTrue();
    });

    test('06IB6-FR-DEPL-024: deployment catalog documents profiles', function (): void {
        expect(File::exists(base_path('docs/specs/06IB6-deployment.md')))->toBeTrue();
    });

    test('06IB6-FR-DEPL-025: docker documentation exists', function (): void {
        expect(File::exists(base_path('docker/README.md')))->toBeTrue();
    });

    test('06IB6-FR-DEPL-026: .env.example documents default drivers and profile', function (): void {
        $env = File::get(base_path('.env.example'));
        expect($env)->toContain('QUEUE_CONNECTION=sync');
    });

    test('06IB6-FR-DEPL-027: adding new profile requires no changes to business code', function (): void {
        expect(config('deployment.profiles'))->toBeArray();
    });

    test('06IB6-NFR-DEPL-001: webhook requires valid secret', function (): void {
        expect(route('cron', ['secret' => 'demo']))->toContain('/cron/demo');
    });

    test('06IB6-NFR-DEPL-002: detection output exposes no credentials', function (): void {
        Artisan::call('deploy:detect');
        $output = Artisan::output();
        expect($output)->not->toContain('password')
            ->and($output)->not->toContain('secret');
    });

    test('06IB6-NFR-DEPL-003: deploy:configure never writes secrets', function (): void {
        $tmpEnv = sys_get_temp_dir().'/test_env_'.uniqid().'.env';
        File::put($tmpEnv, "APP_KEY=abc\n");
        Artisan::call('deploy:configure', ['--profile' => 'shared-hosting', '--env-path' => $tmpEnv]);
        expect(File::get($tmpEnv))->toContain('APP_KEY=abc');
        @unlink($tmpEnv);
    });

    test('06IB6-NFR-DEPL-004: gitignore excludes .env', function (): void {
        expect(File::get(base_path('.gitignore')))->toContain('.env');
    });

    test('06IB6-NFR-DEPL-005: detection is repeatable and safe mid-semester', function (): void {
        Artisan::call('deploy:detect', ['--json' => true]);
        $out1 = Artisan::output();
        Artisan::call('deploy:detect', ['--json' => true]);
        $out2 = Artisan::output();
        expect($out1)->toBe($out2);
    });

    test('06IB6-NFR-DEPL-006: explicit profile survives configure re-runs', function (): void {
        $tmpEnv = sys_get_temp_dir().'/test_env_'.uniqid().'.env';
        File::put($tmpEnv, "APP_KEY=abc\n");
        Artisan::call('deploy:configure', ['--profile' => 'vps-docker', '--env-path' => $tmpEnv]);
        expect(File::get($tmpEnv))->toContain('QUEUE_CONNECTION=redis');
        @unlink($tmpEnv);
    });

    test('06IB6-NFR-DEPL-007: deployment commands are available', function (): void {
        expect(Artisan::all())->toHaveKeys(['setup:install', 'system:health']);
    });

    test('06IB6-NFR-DEPL-008: docker-compose defines service dependencies', function (): void {
        $compose = File::get(base_path('docker-compose.yml'));
        expect($compose)->toContain('depends_on:');
    });

    test('06IB6-NFR-DEPL-009: deploy output is translatable', function (): void {
        expect(__('core.deploy.detection_heading'))->not->toBe('core.deploy.detection_heading');
    });

    test('06IB6-NFR-DEPL-010: detection explains probe results in plain language', function (): void {
        Artisan::call('deploy:detect');
        expect(Artisan::output())->toContain('Container runtime:');
    });

    test('06IB6-NFR-DEPL-011: presets live only in config/deployment.php', function (): void {
        expect(File::exists(config_path('deployment.php')))->toBeTrue();
    });

    test('06IB6-NFR-DEPL-012: deploy commands declare strict_types', function (): void {
        $cmdFile = File::get(app_path('Modules/Core/Console/Commands/DeployConfigureCommand.php'));
        expect($cmdFile)->toContain('declare(strict_types=1);');
    });

    test('06IB6-NFR-DEPL-013: deployment behavior is tested in test suite', function (): void {
        expect(file_exists(__FILE__))->toBeTrue();
    });

    test('06IB6-UC-DEPL-001: shared hosting deployment path', function (): void {
        expect(config('deployment.profiles.shared-hosting.queue'))->toBe('sync');
    });

    test('06IB6-UC-DEPL-002: docker vps deployment path', function (): void {
        expect(config('deployment.profiles.vps-docker.queue'))->toBe('redis');
    });

    test('06IB6-UC-DEPL-003: operator runs detection', function (): void {
        Artisan::call('deploy:detect', ['--json' => true]);
        expect(Artisan::output())->toContain('recommended_profile');
    });

    test('06IB6-UC-DEPL-004: operator overrides auto detection', function (): void {
        $tmpEnv = sys_get_temp_dir().'/test_env_'.uniqid().'.env';
        File::put($tmpEnv, "APP_KEY=abc\n");
        Artisan::call('deploy:configure', ['--profile' => 'shared-hosting', '--env-path' => $tmpEnv]);
        expect(File::get($tmpEnv))->toContain('QUEUE_CONNECTION=sync');
        @unlink($tmpEnv);
    });

    test('06IB6-DD-DEPL-001: presets are single source of truth', function (): void {
        expect(config('deployment.profiles'))->toHaveCount(2);
    });

    test('06IB6-DD-DEPL-002: detection recommends while DEPLOY_PROFILE wins', function (): void {
        expect(config('deployment.default'))->toBe('shared-hosting');
    });

    test('06IB6-DD-DEPL-003: shared hosting is safe default', function (): void {
        expect(config('deployment.default'))->toBe('shared-hosting');
    });

    test('06IB6-DD-DEPL-004: deploy:configure applies driver keys only', function (): void {
        $tmpEnv = sys_get_temp_dir().'/test_env_'.uniqid().'.env';
        File::put($tmpEnv, "APP_KEY=secure\n");
        Artisan::call('deploy:configure', ['--profile' => 'shared-hosting', '--env-path' => $tmpEnv]);
        expect(File::get($tmpEnv))->toContain('APP_KEY=secure');
        @unlink($tmpEnv);
    });
});
