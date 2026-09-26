<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Modules\Core\Console\Commands\DeployDetectCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

describe('W8K2P: docker vps deployment topology, configuration, and pre-release lifecycle', function (): void {
    test('W8K2P-FR-DOCK-001 W8K2P-UC-DOCK-001: docker-compose defines app, web, and db services', function (): void {
        $path = base_path('docker-compose.yml');
        expect(File::exists($path))->toBeTrue();

        $compose = File::get($path);
        expect($compose)->toContain('app:')
            ->and($compose)->toContain('web:')
            ->and($compose)->toContain('db:');
    });

    test('W8K2P-FR-DOCK-002 W8K2P-FR-DOCK-005: app and web build from repository dockerfiles', function (): void {
        expect(File::exists(base_path('Dockerfile')))->toBeTrue()
            ->and(File::exists(base_path('.docker/nginx.Dockerfile')))->toBeTrue()
            ->and(File::exists(base_path('.docker/nginx.conf')))->toBeTrue();
    });

    test('W8K2P-FR-DOCK-006: db service uses mysql 8 with healthcheck', function (): void {
        $compose = File::get(base_path('docker-compose.yml'));
        expect($compose)->toContain('image: mysql:8')
            ->and($compose)->toContain('mysqladmin')
            ->and($compose)->toContain('ping');
    });

    test('W8K2P-FR-DOCK-008: persistent named volumes storage_data and app_data are declared', function (): void {
        $compose = File::get(base_path('docker-compose.yml'));
        expect($compose)->toContain('storage_data:')
            ->and($compose)->toContain('app_data:')
            ->and($compose)->toContain('mysql_data:');
    });

    test('W8K2P-FR-DOCK-011 W8K2P-FR-DOCK-012: services define health checks and restart policies', function (): void {
        $compose = File::get(base_path('docker-compose.yml'));
        expect($compose)->toContain('fpm-healthcheck')
            ->and($compose)->toContain('service_healthy')
            ->and($compose)->toContain('restart: unless-stopped');
    });

    test('W8K2P-FR-DOCK-003 W8K2P-FR-DOCK-004: entrypoint supports queue worker and scheduler flags', function (): void {
        $entrypoint = File::get(base_path('docker/entrypoint.sh'));
        expect($entrypoint)->toContain('RUN_QUEUE')
            ->and($entrypoint)->toContain('queue:work --sleep=3 --tries=3')
            ->and($entrypoint)->toContain('RUN_SCHEDULER')
            ->and($entrypoint)->toContain('schedule:work');
    });

    test('W8K2P-FR-DOCK-007 W8K2P-FR-DOCK-009 W8K2P-FR-DOCK-010 W8K2P-FR-DOCK-013 W8K2P-UC-DOCK-002: Dockerfile installs redis and runtime defaults match tier-1', function (): void {
        $dockerfile = File::get(base_path('Dockerfile'));
        expect($dockerfile)->toContain('pecl install redis')
            ->and($dockerfile)->toContain('docker-php-ext-enable redis');

        $compose = File::get(base_path('docker-compose.yml'));
        expect($compose)->toContain('SESSION_DRIVER: database')
            ->and($compose)->toContain('QUEUE_CONNECTION: sync')
            ->and($compose)->toContain('CACHE_STORE: file');
    });

    test('W8K2P-NFR-DOCK-001 W8K2P-NFR-DOCK-002 W8K2P-NFR-DOCK-003 W8K2P-DD-DOCK-001 W8K2P-DD-DOCK-002: docker deployment security and isolation', function (): void {
        $gitignore = File::get(base_path('.gitignore'));
        expect($gitignore)->toContain('.env');

        $compose = File::get(base_path('docker-compose.yml'));
        expect($compose)->not->toContain('APP_KEY=base64')
            ->and($compose)->toContain('${APP_KEY:?APP_KEY is required}')
            ->and($compose)->toContain(':ro');
    });

    test('W8K2P-FR-DOCK-014 W8K2P-FR-DOCK-016: system defines three-tier deployment lifecycle with production release', function (): void {
        $workflow = File::get(base_path('.github/workflows/release.yml'));
        expect($workflow)->toContain('dev')
            ->and($workflow)->toContain('production');
    });

    test('W8K2P-FR-DOCK-015 W8K2P-UC-DOCK-003 W8K2P-NFR-DOCK-004 W8K2P-DD-DOCK-003: pre-release deploys to staging.internara.web.id via docker', function (): void {
        $spec = File::get(base_path('docs/specs/W8K2P-docker-vps-deployment.md'));
        expect($spec)->toContain('staging.internara.web.id')
            ->and($spec)->toContain('FR-DOCK-015');

        $compose = File::get(base_path('docker-compose.yml'));
        expect($compose)->toContain('SESSION_SECURE_COOKIE');
    });

    test('W8K2P-FR-DOCK-017 W8K2P-UC-DOCK-004: config/deployment.php declares vps-docker preset', function (): void {
        $vps = config('deployment.profiles.vps-docker');
        expect($vps['queue'])->toBe('redis')
            ->and($vps['cache'])->toBe('redis')
            ->and($vps['session'])->toBe('redis')
            ->and($vps['broadcast'])->toBe('log')
            ->and($vps['scheduler'])->toBe('daemon')
            ->and($vps['redis'])->toBeTrue();
    });

    test('W8K2P-FR-DOCK-018: deploy:detect probes and recommends vps-docker when container and daemon present', function (): void {
        $detect = new DeployDetectCommand;
        expect(method_exists($detect, 'probeContainer'))->toBeTrue()
            ->and(method_exists($detect, 'probeRedis'))->toBeTrue()
            ->and(method_exists($detect, 'probeDaemon'))->toBeTrue();
    });

    test('W8K2P-FR-DOCK-019 W8K2P-NFR-DOCK-005: deploy:configure applies vps-docker drivers idempotently without altering secrets or compose', function (): void {
        $dockerComposeBefore = md5_file(base_path('docker-compose.yml'));
        $tmpEnv = sys_get_temp_dir().'/test_vps_env_'.uniqid().'.env';
        File::put($tmpEnv, "APP_KEY=keep_me_safe\n");

        Artisan::call('deploy:configure', ['--profile' => 'vps-docker', '--env-path' => $tmpEnv]);
        $content = File::get($tmpEnv);
        expect($content)->toContain('QUEUE_CONNECTION=redis')
            ->and($content)->toContain('CACHE_STORE=redis')
            ->and($content)->toContain('APP_KEY=keep_me_safe');

        expect(md5_file(base_path('docker-compose.yml')))->toBe($dockerComposeBefore);
        @unlink($tmpEnv);
    });

    test('W8K2P-FR-DOCK-020: system:health serves as post-deploy acceptance gate', function (): void {
        expect(array_key_exists('system:health', Artisan::all()))->toBeTrue();
    });
});
