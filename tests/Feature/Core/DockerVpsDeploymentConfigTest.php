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
});
