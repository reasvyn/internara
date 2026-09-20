<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(LazilyRefreshDatabase::class);

afterEach(function (): void {
    app()->maintenanceMode()->deactivate();
});

describe('E1MSJ system maintenance window', function (): void {
    test('E1MSJ-FR-MAINT-001: opens with a reason and renders a translated notice', function (): void {
        expect(Artisan::call('system:maintenance', [
            '--on' => true,
            '--reason' => 'Enrollment DB migration',
        ]))->toBe(0);

        $payload = app()->maintenanceMode()->data();

        expect(app()->maintenanceMode()->active())->toBeTrue()
            ->and($payload['template'])->toContain(__('sysadmin.maintenance.notice', ['reason' => 'Enrollment DB migration']))
            ->and($payload['template'])->not->toContain('Enrollment DB migration'."' OR 1=1");
    });

    test('E1MSJ-FR-MAINT-001: rejects an open request without a reason', function (): void {
        expect(Artisan::call('system:maintenance', ['--on' => true]))->toBe(2)
            ->and(app()->maintenanceMode()->active())->toBeFalse();
    });

    test('E1MSJ-FR-MAINT-001: closes the window and restores normal availability', function (): void {
        app()->maintenanceMode()->activate([]);

        expect(Artisan::call('system:maintenance', ['--off' => true]))->toBe(0)
            ->and(app()->maintenanceMode()->active())->toBeFalse();
    });
});
