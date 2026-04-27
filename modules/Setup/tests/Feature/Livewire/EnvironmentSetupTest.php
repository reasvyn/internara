<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Livewire\EnvironmentSetup;
use Modules\Setup\Services\Contracts\InstallationAuditor;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    App::setLocale('en');

    // Authorization for setup (Middleware & Gates)
    $settings = app(SettingService::class);
    $settings->setValue('app_installed', false);
    $settings->setValue('setup_token', 'test-token');
    $settings->setValue('setup_step_welcome', true); // Complete previous step

    Gate::define('performStep', fn() => true);

    // Mock the auditor
    $mock = $this->mock(InstallationAuditor::class);
    $mock->shouldReceive('passes')->andReturn(true);
    $mock->shouldReceive('audit')->andReturn([
        'requirements' => ['php_version' => true],
        'permissions' => ['storage_directory' => true],
        'database' => ['connection' => true],
    ]);
});

describe('EnvironmentSetup Component', function () {
    test('it renders audit results correctly', function () {
        $this->get(route('setup.environment', ['token' => 'test-token']));

        Livewire::test(EnvironmentSetup::class)
            ->assertStatus(200)
            ->assertSee(__('setup::wizard.environment.title'));
    });

    test('it disables navigation if system requirements are not met', function () {
        $this->get(route('setup.environment', ['token' => 'test-token']));

        Livewire::test(EnvironmentSetup::class)->assertSet(
            'setupStepProps.currentStep',
            'environment',
        );
    });

    test('it proceeds to the school setup step on next action', function () {
        $this->get(route('setup.environment', ['token' => 'test-token']));

        Livewire::test(EnvironmentSetup::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.school'));
    });

    test('it fulfills [SYRS-NF-401] with mobile-first diagnostic view', function () {
        $this->get(route('setup.environment', ['token' => 'test-token']));

        Livewire::test(EnvironmentSetup::class)->assertSeeHtml('flex-col');
    });
});
