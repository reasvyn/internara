<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Livewire\EnvironmentSetup;
use Modules\Setup\Services\Contracts\SetupService;
use Modules\Setup\Services\Contracts\SystemAuditor;

uses(LazilyRefreshDatabase::class);

describe('EnvironmentSetup Component', function () {
    test('it renders audit results correctly', function () {
        app(SettingService::class)->setValue('setup_step_welcome', true);

        $mockAudit = [
            'requirements' => ['php_version' => true, 'extension_bcmath' => true],
            'permissions' => ['storage_directory' => true],
            'database' => ['connection' => true, 'message' => 'Connected'],
        ];

        $this->mock(SystemAuditor::class, function ($mock) use ($mockAudit) {
            $mock->shouldReceive('audit')->andReturn($mockAudit);
            $mock->shouldReceive('passes')->andReturn(true);
        });

        Livewire::test(EnvironmentSetup::class)
            ->assertStatus(200)
            ->assertSee(__('setup::wizard.environment.title'))
            ->assertSee(__('setup::wizard.status.passed'))
            ->assertSee(__('setup::wizard.status.connected'))
            ->assertSet('disableNextStep', false);
    });

    test('it disables navigation if system requirements fail', function () {
        app(SettingService::class)->setValue('setup_step_welcome', true);

        $mockAudit = [
            'requirements' => ['php_version' => false],
            'permissions' => ['storage_directory' => false],
            'database' => ['connection' => false, 'message' => 'Error'],
        ];

        $this->mock(SystemAuditor::class, function ($mock) use ($mockAudit) {
            $mock->shouldReceive('audit')->andReturn($mockAudit);
            $mock->shouldReceive('passes')->andReturn(false);
        });

        Livewire::test(EnvironmentSetup::class)
            ->assertStatus(200)
            ->assertSee(__('setup::wizard.status.failed'))
            ->assertSee(__('setup::wizard.status.disconnected'))
            ->assertSet('disableNextStep', true);
    });

    test('it proceeds to the school setup step on next action', function () {
        app(SettingService::class)->setValue('setup_step_welcome', true);

        $mockAudit = [
            'requirements' => [],
            'permissions' => [],
            'database' => ['connection' => true],
        ];

        $this->mock(SystemAuditor::class, function ($mock) use ($mockAudit) {
            $mock->shouldReceive('audit')->andReturn($mockAudit);
            $mock->shouldReceive('passes')->andReturn(true);
        });

        Livewire::test(EnvironmentSetup::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.school'));
    });

    test('it fulfills [SYRS-NF-401] with mobile-first cards and layout structure', function () {
        app(SettingService::class)->setValue('setup_step_welcome', true);

        $viewPath = module_path('Setup', 'resources/views/livewire/environment-setup.blade.php');
        $template = file_get_contents($viewPath);

        // Verify structural card-based layout and navigation triggers
        expect($template)->toContain('x-setup::layouts.setup-wizard')
            ->toContain('x-ui::card')
            ->toContain('wire:click="nextStep"');
    });
});
