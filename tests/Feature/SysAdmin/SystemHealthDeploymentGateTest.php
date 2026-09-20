<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

describe('06IB6 deployment health gate via E1MSJ system health', function (): void {
    test('FR-DEPL-023/NFR-DEPL-007/E1MSJ-FR-MAINT-002: health reports deployment subsystems as machine-readable results', function (): void {
        expect(Artisan::call('system:health', ['--json' => true]))->toBe(0);

        $results = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        $byService = collect($results)->keyBy(fn (array $result): string => $result[0]);

        expect($byService)->toHaveKeys([
            __('setup.system.environment'),
            __('setup.system.database'),
            __('setup.system.migration_status'),
            __('setup.system.queue'),
            __('setup.system.cache'),
            __('setup.system.app_key'),
            __('setup.system.storage_link'),
        ])
            ->and($byService[__('setup.system.database')][1])->toBe('OK')
            ->and($byService[__('setup.system.cache')][1])->toBe('OK')
            ->and($byService[__('setup.system.app_key')][1])->toBe('OK');
    });

    test('E1MSJ-FR-MAINT-003: failed-job pressure becomes a warning without failing the gate', function (): void {
        DB::table('failed_jobs')->insert(collect(range(1, 101))->map(fn (int $i): array => [
            'uuid' => "health-check-{$i}",
            'connection' => 'sync',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'test',
            'failed_at' => now(),
        ])->all());

        expect(Artisan::call('system:health', ['--json' => true]))->toBe(0);

        $results = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
        $queue = collect($results)->first(fn (array $result): bool => $result[0] === __('setup.system.queue'));

        expect($queue)->toBe([
            __('setup.system.queue'),
            'WARN',
            '0 pending, 101 failed — consider running queue:prune-failed',
        ]);
    });
});
