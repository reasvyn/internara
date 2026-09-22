<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Modules\Core\Exceptions\UnauthorizedException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

uses(LazilyRefreshDatabase::class);

describe('06IB8: shared hosting deployment behavior', function (): void {
    test('06IB8-FR-HOST-001 06IB8-FR-HOST-002 06IB8-FR-HOST-003: tier 1 drivers operate without external daemons', function (): void {
        expect(config('queue.default'))->toBeIn(['sync', 'database'])
            ->and(config('session.driver'))->toBeIn(['file', 'database', 'cookie', 'array'])
            ->and(config('cache.default'))->toBeIn(['file', 'database', 'array']);
    });

    test('06IB8-FR-HOST-006 06IB8-NFR-HOST-001 06IB8-UC-HOST-001: cron webhook executes schedule with valid secret and rejects invalid', function (): void {
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

    test('06IB8-FR-HOST-008: document root is directed at public directory with entry point', function (): void {
        expect(File::exists(public_path('index.php')))->toBeTrue();
    });

    test('06IB8-FR-HOST-010: database driver supports standard portable connections', function (): void {
        $default = config('database.default');
        expect($default)->toBeIn(['sqlite', 'mysql', 'mariadb', 'pgsql']);
    });

    test('06IB8-NFR-HOST-004: storage directories are writable for deployment assets', function (): void {
        expect(is_writable(storage_path()))->toBeTrue()
            ->and(is_writable(storage_path('framework/views')))->toBeTrue();
    });
});
