<?php

declare(strict_types=1);

namespace Tests\Feature\SysAdmin;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(LazilyRefreshDatabase::class);

describe('E1MSJ: PulseRecordSnapshotsCommand CLI', function (): void {
    test('E1MSJ-FR-SYS-036: pulse:record-snapshots records snapshots for pulse dashboard cards', function (): void {
        $exitCode = Artisan::call('pulse:record-snapshots');

        expect($exitCode)->toBe(0);

        $output = Artisan::output();
        expect($output)->toContain(__('sysadmin.pulse_record.started'))
            ->and($output)->toContain(__('sysadmin.pulse_record.completed'));
    });
});
