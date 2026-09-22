<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use Illuminate\Support\Facades\File;

describe('06IB7: docker vps deployment topology and configuration', function (): void {
    test('06IB7-FR-DOCK-001 06IB7-UC-DOCK-001: docker-compose defines app, web, and db services', function (): void {
        $path = base_path('docker-compose.yml');
        expect(File::exists($path))->toBeTrue();

        $compose = File::get($path);
        expect($compose)->toContain('app:')
            ->and($compose)->toContain('web:')
            ->and($compose)->toContain('db:');
    });

    test('06IB7-FR-DOCK-002 06IB7-FR-DOCK-005: app and web build from repository dockerfiles', function (): void {
        expect(File::exists(base_path('Dockerfile')))->toBeTrue()
            ->and(File::exists(base_path('.docker/nginx.Dockerfile')))->toBeTrue()
            ->and(File::exists(base_path('.docker/nginx.conf')))->toBeTrue();
    });

    test('06IB7-FR-DOCK-006: db service uses mysql 8 with healthcheck', function (): void {
        $compose = File::get(base_path('docker-compose.yml'));
        expect($compose)->toContain('image: mysql:8')
            ->and($compose)->toContain('mysqladmin')
            ->and($compose)->toContain('ping');
    });

    test('06IB7-FR-DOCK-008: persistent named volumes storage_data and app_data are declared', function (): void {
        $compose = File::get(base_path('docker-compose.yml'));
        expect($compose)->toContain('storage_data:')
            ->and($compose)->toContain('app_data:')
            ->and($compose)->toContain('mysql_data:');
    });

    test('06IB7-FR-DOCK-011 06IB7-FR-DOCK-012: services define health checks and restart policies', function (): void {
        $compose = File::get(base_path('docker-compose.yml'));
        expect($compose)->toContain('fpm-healthcheck')
            ->and($compose)->toContain('service_healthy')
            ->and($compose)->toContain('restart: unless-stopped');
    });

    test('06IB7-FR-DOCK-003 06IB7-FR-DOCK-004: entrypoint supports queue worker and scheduler flags', function (): void {
        $entrypoint = File::get(base_path('docker/entrypoint.sh'));
        expect($entrypoint)->toContain('RUN_QUEUE')
            ->and($entrypoint)->toContain('queue:work --sleep=3 --tries=3')
            ->and($entrypoint)->toContain('RUN_SCHEDULER')
            ->and($entrypoint)->toContain('schedule:work');
    });

    test('06IB7-FR-DOCK-007 06IB7-FR-DOCK-009 06IB7-FR-DOCK-010 06IB7-FR-DOCK-013 06IB7-UC-DOCK-002: Dockerfile installs redis and runtime defaults match tier-1', function (): void {
        $dockerfile = File::get(base_path('Dockerfile'));
        expect($dockerfile)->toContain('pecl install redis')
            ->and($dockerfile)->toContain('docker-php-ext-enable redis');

        $compose = File::get(base_path('docker-compose.yml'));
        expect($compose)->toContain('SESSION_DRIVER: database')
            ->and($compose)->toContain('QUEUE_CONNECTION: sync')
            ->and($compose)->toContain('CACHE_STORE: file');
    });

    test('06IB7-NFR-DOCK-001 06IB7-NFR-DOCK-002 06IB7-NFR-DOCK-003 06IB7-DD-DOCK-001 06IB7-DD-DOCK-002: docker deployment security and isolation', function (): void {
        $gitignore = File::get(base_path('.gitignore'));
        expect($gitignore)->toContain('.env');

        $compose = File::get(base_path('docker-compose.yml'));
        expect($compose)->not->toContain('APP_KEY=base64')
            ->and($compose)->toContain('${APP_KEY:?APP_KEY is required}')
            ->and($compose)->toContain(':ro');
    });
});
