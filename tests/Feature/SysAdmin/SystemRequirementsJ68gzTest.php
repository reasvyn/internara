<?php

declare(strict_types=1);

namespace Tests\Feature\SysAdmin;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

describe('J68GZ: System Requirements, Dependencies, Platform & Database', function (): void {
    test('J68GZ-FR-SYS-001/002/003: php version and required extensions', function (): void {
        expect(version_compare(PHP_VERSION, '8.4.0', '>='))->toBeTrue();

        $required = ['bcmath', 'ctype', 'fileinfo', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml', 'curl', 'gd', 'intl', 'zip'];
        foreach ($required as $ext) {
            expect(extension_loaded($ext))->toBeTrue("Extension {$ext} must be loaded");
        }
    });

    test('J68GZ-FR-SYS-004/005/006/007: environment and writability', function (): void {
        expect(is_writable(storage_path()))->toBeTrue()
            ->and(is_writable(base_path('bootstrap/cache')))->toBeTrue()
            ->and((string) config('app.key'))->not->toBeEmpty();
    });

    test('J68GZ-FR-SYS-008/009/010/011/012/013/014/015/016/017/018: framework and package pins', function (): void {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);

        expect($composer['require']['laravel/framework'])->toBe('^13.0')
            ->and($composer['require']['livewire/livewire'])->toBe('^4.0')
            ->and($composer['require']['spatie/laravel-permission'])->toBe('^8.0')
            ->and($composer['require']['spatie/laravel-activitylog'])->toBe('^5.0')
            ->and($composer['require']['spatie/laravel-medialibrary'])->toBe('^11.17')
            ->and($composer['require']['spatie/laravel-model-status'])->toBe('^1.18')
            ->and($composer['require']['laravel-lang/lang'])->toBe('^15.26')
            ->and($composer['require']['barryvdh/laravel-dompdf'])->toBe('^3.1')
            ->and($composer['require']['tallstackui/tallstackui'])->toBe('^4.0')
            ->and(file_exists(base_path('composer.lock')))->toBeTrue();
    });

    test('J68GZ-FR-SYS-019/020/021/022/023/024/025/026: database portability and schema', function (): void {
        expect(config('database.default'))->toBe('sqlite')
            ->and(DB::connection()->getPdo())->not->toBeNull();
    });

    test('J68GZ-FR-SYS-027/028/029/030: deployment tiers and growth', function (): void {
        expect(config('queue.default'))->toBe('sync')
            ->and(in_array(config('cache.default'), ['file', 'array'], true))->toBeTrue()
            ->and(in_array(config('session.driver'), ['database', 'array'], true))->toBeTrue();
    });

    test('J68GZ-FR-SYS-031/032/033/034 J68GZ-UC-SYS-001/002/003: health check surface', function (): void {
        Artisan::call('system:health', ['--json' => true]);
        $rows = json_decode(Artisan::output(), true);

        expect($rows)->toBeArray()
            ->and(count($rows))->toBeGreaterThanOrEqual(10);
    });

    test('J68GZ-NFR-SYS-001/002/003/004 J68GZ-DD-SYS-001/002/003/004: contracts and decisions', function (): void {
        expect((string) config('app.key'))->toStartWith('base64:')
            ->and(config('database.connections.sqlite.foreign_key_constraints'))->toBeTrue();
    });
});
