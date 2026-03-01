<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Services;

use Illuminate\Support\Facades\Gate;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\Contracts\SystemAuditor;
use Modules\Setup\Services\InstallerService;

describe('InstallerService S1 Security', function () {
    test('it enforces authorization before installation', function () {
        $settingService = $this->mock(SettingService::class);
        $auditor = $this->mock(SystemAuditor::class);
        
        Gate::shouldReceive('authorize')->once()->with('install', InstallerService::class);

        // Standardized mock avoiding redeclaration
        $partialService = $this->mock(InstallerService::class, function ($mock) {
            $mock->shouldReceive('ensureEnvFileExists')->andReturn(false);
            $mock->makePartial();
        });

        $partialService->install();
    });
});
