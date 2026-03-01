<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Services;

use Illuminate\Support\Facades\Gate;
use Mockery;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\Contracts\SystemAuditor;
use Modules\Setup\Services\InstallerService;

describe('InstallerService S1 Security', function () {
    test('it enforces authorization before installation', function () {
        $settingService = Mockery::mock(SettingService::class);
        $auditor = Mockery::mock(SystemAuditor::class);
        $service = new InstallerService($settingService, $auditor);

        Gate::shouldReceive('authorize')->once()->with('install', InstallerService::class);

        // We only want to test the gate, so we force an early return
        // by making ensureEnvFileExists return false to avoid running the whole process
        $partialService = Mockery::mock(InstallerService::class, [
            $settingService,
            $auditor,
        ])->makePartial();
        $partialService->shouldReceive('ensureEnvFileExists')->andReturn(false);

        $partialService->install();
    });
});
