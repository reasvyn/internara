<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Unit\Services;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Cache\Lock;
use Mockery;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Services\AppSetupService;
use Modules\Setup\Services\SetupRequirementRegistry;

describe('SetupService', function () {
    beforeEach(function () {
        config(['activitylog.enabled' => false]);

        $this->settingService = Mockery::mock(SettingService::class);
        $this->registry = new SetupRequirementRegistry();

        $this->service = new AppSetupService(
            $this->settingService,
            $this->registry,
        );

        Gate::shouldReceive('authorize')->andReturn(true);

        Cache::spy();
        Cache::shouldReceive('lock')->andReturnUsing(function ($name, $seconds) {
            $lockMock = Mockery::mock(Lock::class);
            $lockMock->shouldReceive('get')->andReturnUsing(fn($callback) => $callback());
            return $lockMock;
        });
    });

    it('identifies if application is not installed', function () {
        $this->settingService
            ->shouldReceive('getValue')
            ->withAnyArgs()
            ->andReturn(false);

        expect($this->service->isAppInstalled())->toBeFalse();
    });

    it('identifies if application is already installed', function () {
        $this->settingService
            ->shouldReceive('getValue')
            ->withAnyArgs()
            ->andReturn(true);

        expect($this->service->isAppInstalled())->toBeTrue();
    });
});